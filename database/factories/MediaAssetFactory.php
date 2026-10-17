<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.fake()->unique()->uuid().'.png',
            'original_filename' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'byte_size' => fake()->numberBetween(1_000, 5_000_000),
            'checksum' => fake()->sha256(),
            'visibility' => 'public',
            'width' => fake()->numberBetween(100, 2_000),
            'height' => fake()->numberBetween(100, 2_000),
        ];
    }
}
