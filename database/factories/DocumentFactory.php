<?php

namespace Database\Factories;

use App\DocumentStatus;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'position' => 0,
            'title' => fake()->sentence(4),
            'content' => [],
            'summary' => fake()->sentence(),
            'label' => fake()->word(),
            'status' => DocumentStatus::Published,
            'is_locked' => false,
            'published_at' => now(),
        ];
    }
}
