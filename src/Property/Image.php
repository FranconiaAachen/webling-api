<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Property;

class Image extends File
{

    public function __construct(
        string $href,
        int $size,
        string $ext,
        string $mime,
        Timestamp $timestamp,
        private readonly array $dimensions
    )
    {
        parent::__construct($href, $size, $ext, $mime, $timestamp);
    }

    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['dimensions'] = $this->dimensions;

        return $data;
    }
}
