<?php


namespace ZF3Belcebur\DoctrineORMFastApi\Factory;


use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF3Belcebur\DoctrineORMFastApi\Module;
use ZF3Belcebur\DoctrineORMFastApi\Resource\ConfigReflection;

class ConfigReflectionFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return ConfigReflection
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $globalConfig = $container->get('Config');
        return new ConfigReflection(
            $container->get(EntityManager::class),
            $globalConfig[Module::CONFIG_KEY]
        );
    }
}
