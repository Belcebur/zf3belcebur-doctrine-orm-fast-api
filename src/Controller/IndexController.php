<?php

namespace ZF3Belcebur\DoctrineORMFastApi\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Exception;
use RuntimeException;
use Zend\Form\Form;
use Zend\Form\FormElementManager\FormElementManagerV2Polyfill;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill;
use Zend\Http\Header\ContentType;
use Zend\Http\PhpEnvironment\Request;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\Strategy\StrategyInterface;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use Zend\View\Model\JsonModel;
use ZF3Belcebur\DoctrineORMFastApi\Resource\ConfigReflection;
use ZF3Belcebur\DoctrineORMFastApi\Resource\DoctrineORMFastApiInterface;
use ZF3Belcebur\DoctrineORMFastApi\Resource\HydratorWithStrategies;
use function array_key_exists;
use function array_merge_recursive;
use function array_pop;
use function array_search;
use function array_shift;
use function array_slice;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;

class IndexController extends AbstractRestfulController
{
    /** @var ConfigReflection */
    protected $configReflection;

    /** @var array */
    protected $strategies = [];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var DoctrineObject
     */
    private $hydrator;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $reflectionTable;

    /**
     * @var FormElementManagerV3Polyfill|FormElementManagerV2Polyfill
     */
    private $formManager;

    /**
     * IndexController constructor.
     *
     * @param ConfigReflection $configReflection
     * @param HydratorWithStrategies $hydrator
     * @param FormElementManagerV3Polyfill $formManager
     */
    public function __construct(ConfigReflection $configReflection, HydratorWithStrategies $hydrator, ?FormElementManagerV3Polyfill $formManager = null)
    {
        $this->configReflection = $configReflection;
        $this->reflectionTable = $configReflection->getReflectionTable();
        $this->em = $configReflection->getEntityManager();
        $this->hydrator = $hydrator;
        $this->formManager = $formManager;
    }

    /**
     * @param MvcEvent $e
     *
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $this->setEntityClassByRoute();
        return parent::onDispatch($e);
    }

    /**
     * @return string|null
     */
    public function setEntityClassByRoute(): ?string
    {
        $entityName = $this->params()->fromRoute('entity');
        $this->entityClass = $this->reflectionTable[$entityName] ?? null;
        if (!$this->entityClass) {
            $key = array_search($entityName, $this->reflectionTable, true);
            $this->entityClass = $this->reflectionTable[$key] ?? null;
        }
        return $this->entityClass;
    }

    /**
     * @return JsonModel
     */
    public function getList(): ?JsonModel
    {
        if (!$this->entityClass) {
            return $this->getReflectionTableAction();
        }
        $limit = (int)$this->params()->fromQuery('limit', 20);
        $filterParams = $this->params()->fromQuery();
        unset($filterParams['limit'], $filterParams['page']);
        $criteria = Criteria::create();
        foreach ($this->filterParamsToEntities($filterParams) as $filterField => $filterValue) {
            $criteria->andWhere(Criteria::expr()->eq($filterField, $filterValue));
        }
        $adapter = new SelectableAdapter($this->em->getRepository($this->entityClass), $criteria);
        $paginator = new Paginator($adapter);
        $paginator
            ->setCurrentPageNumber((int)$this->params()->fromQuery('page', 1))
            ->setItemCountPerPage($limit);

        $result = $this->extractItems($paginator);
        $pageCount = $paginator->count();
        $page = $paginator->getCurrentPageNumber();

        $params = [
            'next-page' => $pageCount > $page && $page > 0 ? $this->url()->fromRoute(null, [], [
                'query' => [
                    'page' => $page + 1,
                    'limit' => $paginator->getItemCountPerPage(),
                ],
                'force_canonical' => true,
            ], true) : null,
            'preview-page' => $page <= $pageCount && $page > 1 ? $this->url()->fromRoute(null, [], [
                'query' => [
                    'page' => $page - 1,
                    'limit' => $paginator->getItemCountPerPage(),
                ],
                'force_canonical' => true,
            ], true) : null,
            'page' => $paginator->getCurrentPageNumber(),
            'pages' => $pageCount,
            'limit' => $paginator->getItemCountPerPage(),
            'total' => $paginator->getTotalItemCount(),
            'result' => $result,
        ];


        $options = (array)$this->params()->fromRoute('options');

        if ($options['paginator'] ?? false) {
            $params['paginator'] = $paginator;
        }

        if ($options['entityManager'] ?? false) {
            $params['entityManager'] = $this->em;
        }

        return new JsonModel($params);
    }

    /**
     * @return JsonModel
     */
    public function getReflectionTableAction(): ?JsonModel
    {
        return new JsonModel($this->reflectionTable);
    }

    private function filterParamsToEntities(array $params): array
    {
        /**
         * @var ClassMetadata $metadata
         * @var ClassMetadata $targetMetadata
         */
        $filter = [];
        $metadata = $this->em->getClassMetadata($this->entityClass);
        foreach ($params as $fieldName => $value) {
            try {
                $info = $metadata->getAssociationMapping($fieldName);
                $targetMetadata = $this->em->getClassMetadata($info['targetEntity']);
                $newFieldName = current($targetMetadata->getIdentifier());
                $newValue = $this->em->getRepository($info['targetEntity'])->findOneBy([$newFieldName => $value]);
            } catch (Exception $exception) {
                $newValue = $value;
            }
            $filter[$fieldName] = $newValue;
        }
        return $filter;
    }

    /**
     * @param $items
     *
     * @return array
     */
    private function extractItems($items): array
    {
        $options = $this->params()->fromRoute('options');

        $result = [];
        $hydrator = $this->getHydratorWithStrategies();

        foreach ($items as $item) {
            $result[] = ($options['hydrate'] ?? true) ? $this->extractItem($item, $hydrator) : $item;
        }

        return $result;
    }

    /**
     * @return HydratorWithStrategies
     */
    private function getHydratorWithStrategies(): HydratorWithStrategies
    {
        $hydrator = $this->hydrator;
        $strategies = $hydrator->getStrategies();
        $strategyInfo = (array)$this->configReflection->getHydratorValueStrategiesByClass()[$this->entityClass];
        foreach ($strategyInfo as $fieldName => $strategyClass) {
            $strategy = null;
            if (array_key_exists($strategyClass, $strategies)) {
                $strategy = $strategies[$strategyClass];
            }
            if ($strategy instanceof StrategyInterface) {
                $hydrator->addStrategy($fieldName, $strategy);
            }
        }

        return $hydrator;
    }

    /**
     * @param                        $item
     * @param HydratorInterface|null $hydrator
     *
     * @return array|null
     */
    private function extractItem($item, HydratorInterface $hydrator = null): ?array
    {
        if (!$hydrator) {
            $hydrator = $this->getHydratorWithStrategies();
        }
        if ($item instanceof DoctrineORMFastApiInterface) {
            return $item->toDoctrineORMFastApi($this->em, $hydrator);
        }
        if ($this->hydrator) {
            return $hydrator->extract($item);
        }

        return null;
    }

    /**
     * @param mixed $data
     *
     * @return JsonModel
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create($data): ?JsonModel
    {
        $formName = $this->params()->fromRoute('form');
        $hydrator = $this->getHydratorWithStrategies();

        /** @var FormElementManagerV2Polyfill $formManager */
        $formManager = $this->formManager;
        if ($formName) {
            /** @var Form $form */
            $form = $formManager->get($formName);
            $form->setData($data);
            if ($form->isValid()) {
                $item = $form->getData();
            } else {
                return new JsonModel([
                    'error-form-messages' => $form->getMessages(),
                    'data' => $data,
                    'status-code' => 500,
                    'error-message' => 'The form contains errors',
                ]);
            }
        } else {
            $item = $hydrator->hydrate($data, new $this->entityClass());
        }

        $this->em->persist($item);
        $this->em->flush($item);

        $extract = $this->extractItem($item);

        return new JsonModel([
            'result' => $extract,
        ]);

    }

    /**
     * @param mixed $id
     *
     * @return null|JsonModel
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete($id): ?JsonModel
    {
        $item = $this->em->getRepository($this->entityClass)->find($id);
        $extract = $this->extractItem($item);
        $this->em->remove($item);
        $this->em->flush($item);

        return new JsonModel([
            'result' => $extract,
        ]);
    }

    /**
     * @param mixed $id
     *
     * @return JsonModel
     * @throws Exception
     */
    public function get($id): ?JsonModel
    {
        $item = $this->em->getRepository($this->entityClass)->find($id);
        if ($item) {
            return new JsonModel([
                'result' => $this->extractItem($item),
            ]);
        }
        throw new RuntimeException(sprintf('%s with id = %s not found', $this->params()->fromRoute('entity'), $id));
    }

    /**
     * @param mixed $id
     * @param mixed $data
     *
     * @return mixed|JsonModel
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update($id, $data): ?JsonModel
    {

        $formName = $this->params()->fromRoute('form');
        $hydrator = $this->getHydratorWithStrategies();
        $item = $this->em->getRepository($this->entityClass)->find($id);
        /** @var Request $request */
        $request = $this->getRequest();

        /** @var ContentType $contentType */
        $contentType = $request->getHeader('Content-Type');
        if (is_string($data) && $contentType->getMediaType() === 'application/x-www-form-urlencoded') {
            $data = json_decode($data, true);
        } elseif ($contentType->getMediaType() === 'multipart/form-data') {
            $data = $this->parsePutData($request);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Invalid Media Type or format');
        }

        if ($item) {
            if ($formName) {
                /** @var Form $form */
                $form = $this->formManager->get($formName);
                $form->setObject($item);
                $form->setData($data);
                if ($form->isValid()) {
                    $item = $form->getData();
                } else {
                    return new JsonModel([
                        'error-form-messages' => $form->getMessages(),
                        'data' => $data,
                        'status-code' => 500,
                        'error-message' => 'The form contains errors',
                    ]);
                }
            } else {
                $item = $hydrator->hydrate($data, $item);
            }

            $this->em->merge($item);
            $this->em->flush($item);
            $extract = $this->extractItem($item);

            return new JsonModel([
                'result' => $extract,
            ]);
        }

        throw new RuntimeException(sprintf('%s with id = %s not found', $this->params()->fromRoute('entity'), $id));

    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function parsePutData(Request $request): array
    {
        /** @var ContentType $contentType */
        $contentType = $request->getHeader('Content-Type');
        $parameters = $contentType->getParameters();
        $exploded = array_map('trim', explode($parameters['boundary'], $request->getContent()));
        array_pop($exploded);
        array_shift($exploded);
        $result = [];
        foreach ($exploded as $data) {
            $explodedData = explode("\n", $data);
            $existKey = preg_match('/name=\"(?<key>.+)"(\s)+/', $explodedData[0], $keyMatches);
            if ($existKey) {
                $key = $keyMatches['key'];
                array_pop($explodedData);
                $value = rtrim(implode("\n", array_slice($explodedData, 2)));
                parse_str("{$key}=$value", $result[]);
            }
        }
        if ($result) {
            return array_merge_recursive(...$result);
        }

        return $result;
    }
}
