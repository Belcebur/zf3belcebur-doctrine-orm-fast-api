<?php


namespace ZF3Belcebur\DoctrineORMFastApi\Resource;


use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineObjectHydrator;
use Zend\Hydrator\NamingStrategy\NamingStrategyInterface;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\DashNamingStrategy;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\EntityStrategy;

class HydratorWithStrategies extends DoctrineObjectHydrator
{

    /** @var array */
    protected $strategies = [];

    /**
     * HydratorWithStrategies constructor.
     *
     * @param array $config
     * @param array $strategies
     * @param ObjectManager $objectManager
     * @param bool $byValue
     */
    public function __construct(array $config, array $strategies, ObjectManager $objectManager, $byValue = true)
    {
        parent::__construct($objectManager, $byValue);
        $namingStrategy = $config['naming_strategy'] instanceOf NamingStrategyInterface ? $config['naming_strategy'] : new DashNamingStrategy();
        $this->strategies = $strategies;
        $this->setNamingStrategy($namingStrategy);
        $this->addStrategy(null, $strategies[EntityStrategy::class]);
    }

    /**
     * @return array
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }


}
