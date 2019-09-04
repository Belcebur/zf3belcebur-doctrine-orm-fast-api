# zf3belcebur-doctrine-orm-fast-api
Quickly create an automatic API CRUD with your Doctrine ORM connection

## See
- [https://packagist.org/explore/?query=zf3belcebur](https://packagist.org/explore/?query=zf3belcebur)

## Installation

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```sh
composer require zf3belcebur/doctrine-orm-fast-api
```

Then add `ZF3Belcebur\DoctrineORMFastApi` to your `config/application.config.php`

## How to use?

`ZF3Belcebur\DoctrineORMFastApi\Controller\IndexController` extends `AbstractRestfulController` and provide the automatic code to all methods.

- All views return a JsonModel

### Config your custom route, by default /bapi
```php
 [
    return [
        ...other configs
        'ZF3Belcebur\DoctrineORMFastApi' => [
            'route' => [
                'bapi' => [
                    'type' => \Zend\Router\Http\Literal::class,
                    'options' => [
                        'route' => '/my-custom-url',
                    ],
                ],
            ],
        ],
    ];
]
```

#### Get available api's
- go to url `/bapi`
#### get list
- go to url `/bapi/entity-name`
#### get list with filter
- go to url `/bapi/entity-name?propertyOrRelationName=propertyOrRelationValue`
    - To filter with a boolean you need to convert to an integer
    - /bapi/product?enable=1
    - /bapi/product?enable=1&family=7

#### Get 
- Do a GET requesto to url `/bapi/entity-name/identifierValue`
    - /bapi/product/25687
#### Update 
- Do a PUT request to url `/bapi/entity-name/identifierValue`
    - PUT -> /bapi/product/25687
#### Create 
- Do a POST request to url `/bapi/entity-name/identifierValue`
    - POST -> /bapi/product/25687
#### DELETE 
- Do a DELETE request to url `/bapi/entity-name/identifierValue`
    - DELETE -> /bapi/product/25687

### Form validation

- If you need to validate data with a ZendForm just add the formName to the url to get it from the FormElementManager
    -    /bapi/product/25687/my-form-name

### Naming strategy

 ```php
  [
     return [
         ...other configs
         'ZF3Belcebur\DoctrineORMFastApi' => [
             'naming_strategy' => DashNamingStrategy::class,
         ],
     ];
 ]
 ```

### Add new strategies

 - You can add other strategies by fieldName, fieldType, or relation.
 
#### Examples

- First Add your strategies by type or by name in your config file 
```php
 [
    'ZF3Belcebur\DoctrineORMFastApi' => [
        'hydrator-value-strategy-by-type' => [
            'datetime' => \Zend\Hydrator\Strategy\DateTimeFormatterStrategy::class,
        ],
        'hydrator-value-strategy-by-name' => [
            'languages' => \Zend\Hydrator\Strategy\ExplodeStrategy::class,
        ],
       ...other configs
    ]
]
```

- Remove the `reflection-file-path` file to regenerate configuration. By Default `getcwd() . '/config/autoload/zf3belcebur-doctrine-orm-fast-api.global.php'`
 
## Custom extract function

- use `\ZF3Belcebur\DoctrineORMFastApi\Resource\DoctrineORMFastApiInterface` in your entity to implements your custom method

```php
namespace ZF3Belcebur\DoctrineORMFastApi\Resource;


use Doctrine\ORM\EntityManager;
use Zend\Hydrator\HydratorInterface;

interface DoctrineORMFastApiInterface
{
    public function toDoctrineORMFastApi(EntityManager $em,HydratorInterface $hydrator): array;
}

```

## Default Config

```php
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
```
