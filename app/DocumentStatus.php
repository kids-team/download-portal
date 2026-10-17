<?php

namespace App;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';

    public function isEditableByEditor(): bool
    {
        return match ($this) {
            self::Draft, self::Review => true,
            self::Published, self::Archived => false,
        };
    }
}
