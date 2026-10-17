<?php

namespace App\Models;

use Database\Factories\AudienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name'])]
class Audience extends Model
{
    /** @use HasFactory<AudienceFactory> */
    use HasFactory;

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
