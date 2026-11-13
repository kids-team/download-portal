<?php

namespace App;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function isEditableByEditor(): bool
    {
        return match ($this) {
            self::Draft => true,
            self::Published => false,
        };
    }
}
