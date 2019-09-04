<?php

namespace ZF3Belcebur\DoctrineORMFastApi;

use Doctrine\ORM\Mapping\ClassMetadata;
use Zend\Hydrator\Strategy\DateTimeFormatterStrategy;
use Zend\Hydrator\Strategy\ExplodeStrategy;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use ZF3Belcebur\DoctrineORMFastApi\Controller\IndexController;
use ZF3Belcebur\DoctrineORMFastApi\Factory\Controller\IndexControllerFactory;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\CollectionStrategy;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\DashNamingStrategy;
use ZF3Belcebur\DoctrineORMFastApi\Strategy\EntityStrategy;

return [
    __NAMESPACE__ => [
        'reflection-file-path' => getcwd() . '/config/autoload/zf3belcebur-doctrine-orm-fast-api.global.php',
        'naming_strategy' => DashNamingStrategy::class,
        'strategies' => [
            EntityStrategy::class => EntityStrategy::class,
            CollectionStrategy::class => CollectionStrategy::class,
        ],
        'reflection-table' => [],
        'hydrator-relation-strategy' => [
            ClassMetadata::MANY_TO_ONE => EntityStrategy::class,
            ClassMetadata::ONE_TO_ONE => EntityStrategy::class,
            ClassMetadata::ONE_TO_MANY => CollectionStrategy::class,
            ClassMetadata::MANY_TO_MANY => CollectionStrategy::class,
        ],
        'hydrator-value-strategy-by-type' => [
            //'datetime' => DateTimeFormatterStrategy::class,
        ],
        'hydrator-value-strategy-by-name' => [
            //'languages' => ExplodeStrategy::class,
            //'defaultLocale' => ExplodeStrategy::class,
        ],
        'hydrator-value-strategy-by-class' => [],
        'route' => [
            __NAMESPACE__ => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/bapi',
                    'defaults' => [
                        'controller' => IndexController::class,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'entity' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:entity[/:id][/:form]',
                            'constraints' => [
                                'entity' => '[a-zA-Z0-9-]+',
                                'form' => '[a-zA-Z0-9-]+',
                                'id' => '[a-zA-Z0-9-]+',
                            ],
                            'defaults' => [
                                'form' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            IndexController::class => IndexControllerFactory::class,
        ],
    ],
];
