<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Repository;

use Terminal42\WeblingApi\Entity\EntityInterface;
use Terminal42\WeblingApi\EntityList;
use Terminal42\WeblingApi\EntityManager;
use Terminal42\WeblingApi\Exception\HttpStatusException;
use Terminal42\WeblingApi\Exception\ParseException;
use Terminal42\WeblingApi\Query\Query;

abstract class AbstractRepository implements RepositoryInterface
{
    public function __construct(
        protected EntityManager $manager
    )
    {
    }

    /**
     * @throws HttpStatusException If there was a problem with the request
     * @throws ParseException      If the JSON data could not be parsed
     */
    public function findAll(array $order = [], bool $full = false): EntityList
    {
        return $this->manager->findAll($this->getType(), null, $order);
    }

    /**
     * @throws HttpStatusException If there was a problem with the request
     * @throws ParseException      If the JSON data could not be parsed
     */
    public function findById(int $id, bool $full = false): EntityInterface
    {
        return $this->manager->find($this->getType(), $id);
    }

    /**
     * @throws HttpStatusException If there was a problem with the request
     * @throws ParseException      If the JSON data could not be parsed
     */
    public function findBy(Query $query, array $order = [], bool $full = false): EntityList
    {
        return $this->manager->findAll($this->getType(), $query, $order);
    }
}
