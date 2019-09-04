<?php
/**
 *
 * User: belcebur
 * Date: 10/05/2018
 * Time: 8:43
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Resource;

use Doctrine\ORM\EntityManager;
use Zend\Filter\FilterChain;
use Zend\Filter\StringToLower;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Filter\Word\SeparatorToDash;
use ZF3Belcebur\DoctrineORMFastApi\Module;
use function file_exists;

class ConfigReflection
{
    /**
     * @var array
     */
    protected $reflectionTable = [];

    /**
     * @var array
     */
    protected $strategies = [];

    /**
     * @var array
     */
    protected $hydratorValueStrategiesByClass = [];

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $config;

    /**
     * ConfigReflection constructor.
     *
     * @param EntityManager $entityManager
     * @param array $config
     */
    public function __construct(EntityManager $entityManager, array $config = [])
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->filePath = $config['reflection-file-path'] ?? '';

        if (file_exists($this->filePath)) {
            $this->reflectionTable = (array)include $this->filePath;
        }

        if (!$this->reflectionTable) {
            $this->init();
            $this->config['reflection-table'] = $this->reflectionTable;
            $this->config['hydrator-value-strategy-by-class'] = $this->hydratorValueStrategiesByClass;
            $this->config['strategies'] = $this->strategies;
        } else {
            $this->reflectionTable = $this->config['reflection-table'];
            $this->hydratorValueStrategiesByClass = $this->config['hydrator-value-strategy-by-class'];
            $this->strategies = $this->config['strategies'];
        }

    }

    protected function init(): array
    {

        $hydratorValueStrategiesByClass = [];
        $relationStrategies = $this->config['hydrator-relation-strategy'];
        $strategiesByType = $this->config['hydrator-value-strategy-by-type'];
        $strategiesByName = $this->config['hydrator-value-strategy-by-name'];

        $metas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $filterChain = new FilterChain();
        $filterChain
            ->attach(new CamelCaseToDash())
            ->attach(new SeparatorToDash('_'))
            ->attach(new StringToLower());
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
        foreach ($metas as $meta) {
            try {
                $reflectionClass = new \ReflectionClass($meta->getName());
                if (!$reflectionClass->isAbstract()) {
                    $tableNameFiltered = $filterChain->filter($meta->getTableName());
                    $this->reflectionTable[$tableNameFiltered] = $meta->getName();
                    $hydratorValueStrategy = [$meta->getName() => []];

                    foreach (\array_merge($meta->getAssociationMappings(), $meta->fieldMappings) as $mapping) {
                        if (\array_key_exists($mapping['fieldName'], $strategiesByName)) {
                            $hydratorValueStrategy[$meta->getName()][$mapping['fieldName']] = $strategiesByName[$mapping['fieldName']];
                            $this->strategies[$strategiesByName[$mapping['fieldName']]] = $strategiesByName[$mapping['fieldName']];
                        } elseif (\array_key_exists($mapping['type'], $strategiesByType)) {
                            $hydratorValueStrategy[$meta->getName()][$mapping['fieldName']] = $strategiesByType[$mapping['type']];
                            $this->strategies[$strategiesByType[$mapping['type']]] = $strategiesByType[$mapping['type']];
                        } elseif (\array_key_exists($mapping['type'], $relationStrategies)) {
                            $hydratorValueStrategy[$meta->getName()][$mapping['fieldName']] = $relationStrategies[$mapping['type']];
                            $this->strategies[$relationStrategies[$mapping['type']]] = $relationStrategies[$mapping['type']];
                        }
                    }
                    $hydratorValueStrategiesByClass[] = $hydratorValueStrategy;
                }
            } catch (\Exception $exception) {
//                 Omited class
            }
        }
        $this->hydratorValueStrategiesByClass = \array_merge_recursive(...$hydratorValueStrategiesByClass);
        $configStructure = [Module::CONFIG_KEY => ['reflection-table' => $this->reflectionTable, 'hydrator-value-strategy-by-class' => $this->hydratorValueStrategiesByClass, 'strategies' => $this->strategies]];
        $content = '<?php namespace ' . Module::CONFIG_KEY . "; \n\n return " . var_export($configStructure, true) . ';';
        \file_put_contents($this->filePath, $content);
        return $this->config;
    }

    /**
     * @return array
     */
    public function getReflectionTable(): array
    {
        return $this->reflectionTable;
    }

    /**
     * @return array
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getHydratorValueStrategiesByClass(): array
    {
        return $this->hydratorValueStrategiesByClass;
    }

}
