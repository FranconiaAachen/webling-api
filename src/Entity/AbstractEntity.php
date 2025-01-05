<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Entity;

use Terminal42\WeblingApi\EntityList;

abstract class AbstractEntity implements EntityInterface
{
    public function __construct(
        protected ?int $id = null,
        protected bool $readonly = false,
        protected array $properties = [],
        protected array $children = [],
        protected ?EntityList $parents = null,
        protected array $links = []
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): EntityInterface
    {
        $this->id = $id;

        return $this;
    }

    public function unsetId(): EntityInterface
    {
        $this->id = null;

        return $this;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): EntityInterface
    {
        $this->properties = $properties;

        return $this;
    }

    public function getProperty(string $name): mixed
    {
        return $this->properties[$name];
    }

    public function setProperty(string $name, mixed $value): EntityInterface
    {
        $this->properties[$name] = $value;

        return $this;
    }

    public function getChildren(string $type): ?EntityList
    {
        return $this->children[$type] ?? null;
    }

    public function getParents(): ?EntityList
    {
        return $this->parents;
    }

    public function setParents(EntityList $parents): EntityInterface
    {
        $this->parents = $parents;

        return $this;
    }

    public function getLinks(string $type): ?EntityList
    {
        return $this->links[$type] ?? null;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->getType(),
            'readonly' => $this->readonly,
            'properties' => $this->properties,
            // do not add "children" here since they are readonly in the API
            'parents' => $this->parents,
            'links' => $this->links,
        ];

        return $data;
    }
}
