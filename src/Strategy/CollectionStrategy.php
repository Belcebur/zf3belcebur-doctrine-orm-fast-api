<?php
/**
 *
 * User: belcebur
 * Date: 09/05/2018
 * Time: 13:48
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Strategy;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use DoctrineModule\Stdlib\Hydrator\Strategy\AllowRemoveByReference;
use function get_class;

class CollectionStrategy extends AllowRemoveByReference
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /** @var DoctrineObject */
    private $hydrator;

    /**
     * EntityStrategy constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->hydrator = new DoctrineObject($this->entityManager);
    }

    public function extract($collection)
    {
        $data = [];
        if ($collection instanceof PersistentCollection) {
            foreach ($collection as $value) {
                $data[] = $this->extractItem($value);
            }
        }

        return $data;
    }

    public function extractItem($value)
    {
        $key = current($this->getIdentityField($value));
        $extract = $this->hydrator->extract($value);
        return $extract[$key] ?? null;
    }

    private function getIdentityField($value): array
    {
        $meta = $this->entityManager->getClassMetadata(get_class($value));
        if ($meta instanceof ClassMetadata) {
            return $meta->getIdentifierFieldNames();
        }

        return [];
    }

}
