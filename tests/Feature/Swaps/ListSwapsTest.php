<?php

namespace Tests\Feature\Swaps;

use App\Models\Artist;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListSwapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_artist_only_sees_their_own_swaps(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->create(['user_id' => $user->id]);

        $otherArtist1 = Artist::factory()->create();
        $otherArtist2 = Artist::factory()->create();
        $otherArtist3 = Artist::factory()->create();

        Swap::factory()->create(['artist_a_id' => $myArtist->id, 'artist_b_id' => $otherArtist1->id]);
        Swap::factory()->create(['artist_a_id' => $otherArtist2->id, 'artist_b_id' => $myArtist->id]);
        Swap::factory()->create(['artist_a_id' => $otherArtist2->id, 'artist_b_id' => $otherArtist3->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/swaps');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'swaps');
    }

    public function test_an_admin_sees_all_swaps(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Swap::factory()->count(3)->create();

        Passport::actingAs($admin);

        $response = $this->getJson('/api/swaps');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'swaps');
    }

    public function test_listing_swaps_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/swaps');

        $response->assertStatus(401);
    }
}