<?php

namespace Database\Factories;

use App\Models\Download;
use App\Models\DownloadDailyStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DownloadDailyStatistic>
 */
class DownloadDailyStatisticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'download_id' => Download::factory(),
            'downloaded_on' => fake()->date(),
            'download_count' => fake()->numberBetween(1, 1_000),
        ];
    }
}
