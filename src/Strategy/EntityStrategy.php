<?php
/**
 *
 * User: belcebur
 * Date: 09/05/2018
 * Time: 13:48
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Strategy;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Zend\Hydrator\Strategy\StrategyInterface;
use function get_class;

class EntityStrategy implements StrategyInterface
{

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var DoctrineObject
     */
    private $hydrator;


    /**
     * EntityStrategy constructor.
     *
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->hydrator = new DoctrineObject($this->objectManager);
    }

    public function extract($value)
    {
        $key = current($this->getIdentityField($value));
        $extract = $this->hydrator->extract($value);

        return $extract[$key] ?? null;
    }

    private function getIdentityField($value): array
    {
        $meta = $this->objectManager->getClassMetadata(get_class($value));
        if ($meta instanceof ClassMetadata) {
            return $meta->getIdentifierFieldNames();
        }

        return [];
    }

    public function hydrate($value)
    {
        // TODO: Implement hydrate() method.
    }

}
