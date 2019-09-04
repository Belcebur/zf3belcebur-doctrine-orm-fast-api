<?php


namespace ZF3Belcebur\DoctrineORMFastApi\Factory;


use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF3Belcebur\DoctrineORMFastApi\Resource\ConfigReflection;
use ZF3Belcebur\DoctrineORMFastApi\Resource\HydratorWithStrategies;

class HydratorWithStrategiesFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return HydratorWithStrategies
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $configReflection = $container->get(ConfigReflection::class);
        $strategies = [];

        foreach ($configReflection->getStrategies() as $strategyClass) {
            if ($container->has($strategyClass)) {
                $strategies[$strategyClass] = $container->get($strategyClass);
            }
        }

        return new HydratorWithStrategies(
            $configReflection->getConfig(),
            $strategies,
            $container->get(EntityManager::class),
            true
        );
    }

}
