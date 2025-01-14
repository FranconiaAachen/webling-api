<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Entity;

use DateMalformedStringException;
use InvalidArgumentException;
use Terminal42\WeblingApi\Property\Date;
use Terminal42\WeblingApi\Property\File;
use Terminal42\WeblingApi\Property\Image;
use Terminal42\WeblingApi\Property\Timestamp;
use UnderflowException;

trait GeneratorTrait
{
    /**
     * @var array
     */
    private array $definition;

    public function setDefinition(array $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * @throws DateMalformedStringException
     */
    protected function valueFromProperty($name, $value): string|int|Image|Date|Timestamp|File|null
    {
        $property = null;
        foreach ((array)$this->definition['properties'] as $data)
        {
            if ($name === $data['title'])
            {
                $property = $data;
                break;
            }
        }

        if (null === $property)
        {
            throw new UnderflowException(sprintf('Webling Error: Property with title "%s" does not exist in the %s definition.', $name, __CLASS__));
        }

        $datatype = $property['datatype'];

        return match ($datatype)
        {
            'autoincrement', 'int', 'numeric', 'bool', 'text', 'longtext' => $value,
            'file' => new File(
                $value['href'],
                $value['size'],
                $value['ext'],
                $value['mime'],
                new Timestamp($value['timestamp'])
            ),
            'image' => new Image(
                $value['href'],
                $value['size'],
                $value['ext'],
                $value['mime'],
                new Timestamp($value['timestamp']),
                $value['dimensions']
            ),
            'date' => null === $value ? null : new Date($value),
            'timestamp' => null === $value ? null : new Timestamp($value),
            default => throw new InvalidArgumentException(sprintf('Type "%s" is not supported.', $datatype)),
        };

    }

    protected function getPropertyNameById($id)
    {
        foreach ((array)$this->definition['properties'] as $data)
        {
            if ($id === $data['id'])
            {
                return $data['title'];
            }
        }

        throw new UnderflowException(sprintf('Webling Error: Property with ID %s does not exist in the %s definition.', $id, __CLASS__));
    }
}
