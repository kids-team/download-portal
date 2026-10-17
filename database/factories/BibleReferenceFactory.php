<?php

namespace Database\Factories;

use App\Models\BibleReference;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleReference>
 */
class BibleReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'translation' => 'slt',
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => 16,
            'position' => 0,
        ];
    }
}
