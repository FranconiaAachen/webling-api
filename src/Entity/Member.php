<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Entity;

class Member extends AbstractEntity
{
    public const string IMAGE_ORIGINAL = 'original';
    public const string IMAGE_THUMB = 'thumb';
    public const string IMAGE_MINI = 'mini';

    public static function getType(): string
    {
        return 'member';
    }

    public static function getParentType(): string
    {
        return 'membergroup';
    }

    /**
     * @internal not yet implemented
     */
    public function getImage($property, $size = self::IMAGE_ORIGINAL): void
    {
        // TODO: implement method
    }

    /**
     * @internal not yet implemented
     */
    public function getFile($property): void
    {
        // TODO: implement method
    }
}
