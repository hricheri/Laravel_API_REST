<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
        ];
    }
}