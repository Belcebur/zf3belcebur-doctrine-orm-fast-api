<?php

namespace ZF3Belcebur\DoctrineORMFastApi\Factory\Controller;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF3Belcebur\DoctrineORMFastApi\Controller\IndexController;
use ZF3Belcebur\DoctrineORMFastApi\Resource\ConfigReflection;
use ZF3Belcebur\DoctrineORMFastApi\Resource\HydratorWithStrategies;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return IndexController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $configReflection = $container->get(ConfigReflection::class);
        return new IndexController(
            $configReflection,
            $container->get(HydratorWithStrategies::class),
            $container->has('FormElementManager') ? $container->get('FormElementManager') : null
        );
    }
}
