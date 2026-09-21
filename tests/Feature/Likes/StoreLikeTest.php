<?php

namespace Tests\Feature\Likes;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StoreLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_artist_can_like_another_artist(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->create();

        Passport::actingAs($user);

        $response = $this->postJson('/api/likes', [
            'liked_artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('likes', [
            'liker_artist_id' => $myArtist->id,
            'liked_artist_id' => $otherArtist->id,
        ]);
    }

    public function test_liking_the_same_artist_twice_does_not_create_a_duplicate(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->create();

        Passport::actingAs($user);

        $this->postJson('/api/likes', ['liked_artist_id' => $otherArtist->id]);
        $response = $this->postJson('/api/likes', ['liked_artist_id' => $otherArtist->id]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('likes', 1);
    }

    public function test_an_unverified_artist_cannot_like_another_artist(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id, 'is_verified' => false]);
        $otherArtist = Artist::factory()->create();

        Passport::actingAs($user);

        $response = $this->postJson('/api/likes', [
            'liked_artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_liking_an_artist_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->postJson('/api/likes', [
            'liked_artist_id' => $artist->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_liking_an_artist_fails_without_liked_artist_id(): void
    {
        $user = User::factory()->create();
        Artist::factory()->verified()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/likes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['liked_artist_id']);
    }
}