<?php
/**
 *
 * User: belcebur
 * Date: 03/05/2018
 * Time: 12:15
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Resource;


use Doctrine\ORM\EntityManager;
use Zend\Hydrator\HydratorInterface;

interface DoctrineORMFastApiInterface
{
    public function toDoctrineORMFastApi(EntityManager $em,HydratorInterface $hydrator): array;
}
