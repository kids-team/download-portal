<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
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
            'disk' => 'public',
            'path' => 'downloads/'.fake()->unique()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'byte_size' => fake()->numberBetween(1_000, 5_000_000),
            'checksum' => fake()->sha256(),
            'visibility' => 'public',
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'position' => 0,
            'is_published' => true,
        ];
    }
}
