<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'liker_artist_id' => Artist::factory(),
            'liked_artist_id' => Artist::factory(),
        ];
    }
}