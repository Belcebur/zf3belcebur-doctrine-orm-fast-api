<?php
/**
 *
 * User: belcebur
 * Date: 09/05/2018
 * Time: 13:48
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Factory\Strategy;


use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\EntityStrategy;

class EntityStrategyFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return EntityStrategy
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new EntityStrategy(
            $container->get(EntityManager::class)
        );
    }
}
