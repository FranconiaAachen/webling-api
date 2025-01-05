<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Property;

class File implements \JsonSerializable
{
    public function __construct(
        private readonly string $href,
        private readonly int $size,
        private readonly string $ext,
        private readonly string $mime,
        private readonly Timestamp $timestamp
    )
    {
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getExt(): string
    {
        return $this->ext;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function getTimestamp(): Timestamp
    {
        return $this->timestamp;
    }

    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href,
            'size' => $this->size,
            'ext' => $this->ext,
            'mime' => $this->mime,
            'timestamp' => (string) $this->timestamp,
        ];
    }
}
