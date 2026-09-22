<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class SwapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'artist_a_id' => Artist::factory(),
            'artist_b_id' => Artist::factory(),
            'status' => 'pending',
            'confirmed_by_a' => false,
            'confirmed_by_b' => false,
        ];
    }
}