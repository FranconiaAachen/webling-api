<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi;

use InvalidArgumentException;
use Terminal42\WeblingApi\Entity\EntityInterface;
use Terminal42\WeblingApi\Exception\ApiErrorException;
use Terminal42\WeblingApi\Exception\HttpStatusException;
use Terminal42\WeblingApi\Exception\NotFoundException;
use Terminal42\WeblingApi\Exception\ParseException;
use Terminal42\WeblingApi\Query\Query;
use Terminal42\WeblingApi\Repository\RepositoryInterface;
use UnexpectedValueException;

class EntityManager
{
    public const int API_VERSION = 1;

    /**
     * @var array
     */
    private array $entities = [];

    /**
     * @var array|null
     */
    private ?array $definition = null;

    /**
     * Constructor.
     *
     * @param ClientInterface        $client  the HTTP client to make requests to the server
     * @param EntityFactoryInterface $factory a factory capable of creating entities
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly EntityFactoryInterface $factory
    )
    {
    }

    /**
     * Returns the definition of entities.
     *
     * @throws HttpStatusException If there was a problem with the request
     * @throws ParseException      If the JSON data could not be parsed
     * @throws NotFoundException   If the API returned a HTTP status code 404
     * @throws ApiErrorException   If the API returned an error message
     *
     * @return array
     */
    public function getDefinition(): array
    {
        if (null === $this->definition) {
            $this->definition = $this->client->get('/definition', ['format' => 'full']);
        }

        return $this->definition;
    }

    /**
     * Returns the latest revision number from replication dataset.
     *
     * @return int
     */
    public function getLatestRevisionId(): int
    {
        $result = $this->client->get('/replicate');

        return (int) $result['revision'];
    }

    /**
     * Returns the changeset for latest revision compared to given ID.
     */
    public function getChanges(int $revisionId): Changes
    {
        return new Changes($revisionId, $this->client->get('/replicate/'.(int) $revisionId), $this);
    }

    /**
     * Gets the factory instance used by this entity manager.
     *
     * @return EntityFactoryInterface
     */
    public function getFactory(): EntityFactoryInterface
    {
        return $this->factory;
    }

    /**
     * Finds all entities for a given type.
     *
     * @param string     $type  The entity type
     * @param Query|null $query A property query from the QueryBuilder
     * @param array      $order a sorting array where key is property and value is direction (see constants)
     * @param bool $full request full data not only IDs
     *
     * @return EntityList
     */
    public function findAll(string $type, ?Query $query = null, array $order = [], bool $full = false): EntityList
    {
        $params = [
            'filter' => (string) $query,
            'order' => $this->prepareOrder($order),
        ];

        if ($full)
            $params['format'] = 'full';

        $data = $this->client->get("/$type", array_filter($params));

        if (!$full)
            return new EntityList($type, $data['objects'], $this);

        $ids = [];
        foreach ($data as $object)
        {
            $id = $object['id'];
            $ids[] = $id;
            $entity = $this->factory->create($this, $object, $id);
            $this->entities[$type][$id] = $entity;
        }

        return new EntityList($type, $ids, $this);
    }

    /**
     * Find an entity by ID of given type.
     *
     * @throws HttpStatusException If there was a problem with the request
     * @throws ParseException      If the JSON data could not be parsed
     * @throws NotFoundException   If the API returned a HTTP status code 404
     * @throws ApiErrorException   If the API returned an error message
     *
     * @return mixed
     */
    public function find(string $type, int $id): EntityInterface
    {
        if (!isset($this->entities[$type][$id])) {
            $data = $this->client->get("/$type/$id");
            $entity = $this->factory->create($this, $data, $id);

            $this->entities[$type][$id] = $entity;
        }

        return $this->entities[$type][$id];
    }

    /**
     * Add or update the entity in Webling.
     *
     * @throws InvalidArgumentException If the entity is readonly
     * @throws HttpStatusException       If there was a problem with the request
     * @throws NotFoundException         If the API returned a HTTP status code 404
     * @throws ApiErrorException         If the API returned an error message
     */
    public function persist(EntityInterface $entity): void
    {
        if ($entity->isReadonly()) {
            throw new InvalidArgumentException('The entity must not be readonly.');
        }

        if (null === $entity->getId()) {
            $this->create($entity);
        } else {
            $this->update($entity);
        }

        $this->entities[$entity->getType()][$entity->getId()] = $entity;
    }

    /**
     * Delete entity from Webling.
     *
     * @throws UnexpectedValueException If the entity does not have an ID
     * @throws InvalidArgumentException If the entity is readonly
     * @throws HttpStatusException       If there was a problem with the request
     * @throws NotFoundException         If the API returned a HTTP status code 404
     * @throws ApiErrorException         If the API returned an error message
     */
    public function remove(EntityInterface $entity): void
    {
        $this->validateHasId($entity);

        if ($entity->isReadonly()) {
            throw new InvalidArgumentException('The entity must not be readonly.');
        }

        $id = $entity->getId();
        $type = $entity->getType();

        $this->client->delete("/$type/$id");

        unset($this->entities[$type][$id]);
        $entity->unsetId();
    }

    /**
     * Creates a new entity manager for the given Webling account.
     */
    public static function createForAccount(string $subdomain, string $apiKey): self
    {
        return new static(new Client($subdomain, $apiKey, static::API_VERSION), new EntityFactory());
    }

    /**
     * Creates an entity in Webling.
     *
     * @throws HttpStatusException If there was a problem with the request
     * @throws NotFoundException   If the API returned a HTTP status code 404
     * @throws ApiErrorException   If the API returned an error message
     */
    private function create(EntityInterface $entity): void
    {
        $type = $entity->getType();
        $data = json_encode($entity);

        $result = $this->client->post("/$type", $data);

        $entity->setId((int) $result);
    }

    /**
     * Updates an entity in Webling.
     *
     * @throws HttpStatusException If there was a problem with the request
     * @throws NotFoundException   If the API returned a HTTP status code 404
     * @throws ApiErrorException   If the API returned an error message
     */
    private function update(EntityInterface $entity): void
    {
        $id = $entity->getId();
        $type = $entity->getType();
        $data = json_encode($entity);

        $this->client->put("/$type/$id", $data);
    }

    /**
     * Throws exception if entity does not have an ID.
     *
     * @throws UnexpectedValueException
     */
    private function validateHasId(EntityInterface $entity): void
    {
        if (null === $entity->getId()) {
            throw new UnexpectedValueException('The entity must have an ID.');
        }
    }

    /**
     * Validates an order config and converts to query string.
     *
     * @throws InvalidArgumentException if the order contains invalid sorting directions
     */
    private function prepareOrder(array $order): string
    {
        $props = [];
        $directions = [RepositoryInterface::DIRECTION_ASC, RepositoryInterface::DIRECTION_DESC];

        foreach ($order as $property => $direction) {
            if (!\in_array($direction, $directions, true)) {
                throw new InvalidArgumentException(sprintf('Invalid sorting direction "%s" for property "%s"', $property, $direction));
            }

            $props[] = sprintf('`%s` %s', $property, $direction);
        }

        return implode(', ', $props);
    }
}
