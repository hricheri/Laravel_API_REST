<?php

namespace Tests\Feature\Availability;

use App\Models\Artist;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShowAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_artist_can_view_another_artists_availability(): void
    {
        $user = User::factory()->create();
        Artist::factory()->verified()->create(['user_id' => $user->id]);

        $otherArtist = Artist::factory()->create();
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-10-15']);

        Passport::actingAs($user);

        $response = $this->getJson("/api/artists/{$otherArtist->id}/availabilities");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'availabilities');
    }

    public function test_an_unverified_artist_can_view_their_own_availability(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id, 'is_verified' => false]);
        Availability::factory()->create(['artist_id' => $artist->id, 'date' => '2026-10-15']);

        Passport::actingAs($user);

        $response = $this->getJson("/api/artists/{$artist->id}/availabilities");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'availabilities');
    }

    public function test_an_unverified_artist_cannot_view_another_artists_availability(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id, 'is_verified' => false]);

        $otherArtist = Artist::factory()->create();
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-10-15']);

        Passport::actingAs($user);

        $response = $this->getJson("/api/artists/{$otherArtist->id}/availabilities");

        $response->assertStatus(403);
    }

    public function test_viewing_availability_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->getJson("/api/artists/{$artist->id}/availabilities");

        $response->assertStatus(401);
    }

    public function test_viewing_availability_for_nonexistent_artist_returns_404(): void
    {
        $user = User::factory()->create();
        Artist::factory()->verified()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/artists/99999/availabilities');

        $response->assertStatus(404);
    }
}