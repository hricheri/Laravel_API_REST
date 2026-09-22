<?php

namespace Tests\Feature\Swaps;

use App\Models\Artist;
use App\Models\Availability;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StoreSwapTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_swap_is_created_with_calculated_overlap_dates(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->verified()->create();

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $otherArtist->id]);
        Like::factory()->create(['liker_artist_id' => $otherArtist->id, 'liked_artist_id' => $myArtist->id]);

        Availability::factory()->create(['artist_id' => $myArtist->id, 'date' => '2026-11-01']);
        Availability::factory()->create(['artist_id' => $myArtist->id, 'date' => '2026-11-02']);
        Availability::factory()->create(['artist_id' => $myArtist->id, 'date' => '2026-11-05']);

        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-11-02']);
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-11-03']);
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-11-05']);

        Passport::actingAs($user);

        $response = $this->postJson('/api/swaps', [
            'artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'swap' => [
                    'status' => 'pending',
                    'start_date' => '2026-11-02',
                    'end_date' => '2026-11-05',
                ],
            ]);

        $this->assertDatabaseHas('swaps', [
            'artist_a_id' => $myArtist->id,
            'artist_b_id' => $otherArtist->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
        ]);
    }

    public function test_a_swap_is_created_with_null_dates_when_no_overlap(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->verified()->create();

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $otherArtist->id]);
        Like::factory()->create(['liker_artist_id' => $otherArtist->id, 'liked_artist_id' => $myArtist->id]);

        Availability::factory()->create(['artist_id' => $myArtist->id, 'date' => '2026-11-01']);
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-12-01']);

        Passport::actingAs($user);

        $response = $this->postJson('/api/swaps', [
            'artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'swap' => [
                    'status' => 'pending',
                    'start_date' => null,
                    'end_date' => null,
                ],
            ]);
    }

    public function test_a_swap_cannot_be_created_without_a_mutual_match(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->verified()->create();

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $otherArtist->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/swaps', [
            'artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('swaps', 0);
    }

    public function test_an_unverified_artist_cannot_create_a_swap(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id, 'is_verified' => false]);
        $otherArtist = Artist::factory()->verified()->create();

        Passport::actingAs($user);

        $response = $this->postJson('/api/swaps', [
            'artist_id' => $otherArtist->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_creating_a_swap_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->postJson('/api/swaps', [
            'artist_id' => $artist->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_creating_a_swap_fails_with_nonexistent_artist_id(): void
    {
        $user = User::factory()->create();
        Artist::factory()->verified()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/swaps', [
            'artist_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id']);
    }
}