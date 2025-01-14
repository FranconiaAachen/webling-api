<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Repository;

use Terminal42\WeblingApi\Entity\EntityInterface;
use Terminal42\WeblingApi\EntityList;
use Terminal42\WeblingApi\Query\Query;

interface RepositoryInterface
{
    public const string DIRECTION_ASC = 'ASC';
    public const string DIRECTION_DESC = 'DESC';

    /**
     * Gets the entity type for this repository.
     */
    public function getType(): string;

    /**
     * Finds all entities of this type.
     *
     * @param array $order a sorting array where key is property and value is direction (see constants)
     * @param bool $full request full data not only IDs
     *
     * @return EntityList
     */
    public function findAll(array $order = [], bool $full = false): EntityList;

    /**
     * Find entity by ID.
     *
     * @param int $id The entity ID
     * @param bool $full request full data not only IDs
     *
     * @return EntityInterface
     */
    public function findById(int $id, bool $full = false): EntityInterface;

    /**
     * Find entities with given properties.
     *
     * @param Query $query A property query from the QueryBuilder
     * @param array $order a sorting array where key is property and value is direction (see constants)
     * @param bool $full request full data not only IDs
     *
     * @return EntityList
     */
    public function findBy(Query $query, array $order = [], bool $full = false): EntityList;
}
