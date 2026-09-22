<?php

namespace Tests\Feature\Swaps;

use App\Models\Artist;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ConfirmSwapTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_a_confirming_does_not_confirm_the_whole_swap(): void
    {
        $userA = User::factory()->create();
        $artistA = Artist::factory()->create(['user_id' => $userA->id]);
        $artistB = Artist::factory()->create();

        $swap = Swap::factory()->create([
            'artist_a_id' => $artistA->id,
            'artist_b_id' => $artistB->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
        ]);

        Passport::actingAs($userA);

        $response = $this->putJson("/api/swaps/{$swap->id}");

        $response->assertStatus(200)
            ->assertJson([
                'swap' => [
                    'status' => 'pending',
                    'confirmed_by_a' => true,
                    'confirmed_by_b' => false,
                ],
            ]);
    }

    public function test_swap_becomes_confirmed_when_both_artists_confirm(): void
    {
        $userA = User::factory()->create();
        $artistA = Artist::factory()->create(['user_id' => $userA->id]);

        $userB = User::factory()->create();
        $artistB = Artist::factory()->create(['user_id' => $userB->id]);

        $swap = Swap::factory()->create([
            'artist_a_id' => $artistA->id,
            'artist_b_id' => $artistB->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
        ]);

        Passport::actingAs($userA);
        $this->putJson("/api/swaps/{$swap->id}");

        Passport::actingAs($userB);
        $response = $this->putJson("/api/swaps/{$swap->id}");

        $response->assertStatus(200)
            ->assertJson([
                'swap' => [
                    'status' => 'confirmed',
                    'confirmed_by_a' => true,
                    'confirmed_by_b' => true,
                ],
            ]);
    }

    public function test_an_artist_not_part_of_the_swap_cannot_confirm_it(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        $swap = Swap::factory()->create();

        Passport::actingAs($user);

        $response = $this->putJson("/api/swaps/{$swap->id}");

        $response->assertStatus(403);
    }

    public function test_confirming_a_swap_fails_without_authentication(): void
    {
        $swap = Swap::factory()->create();

        $response = $this->putJson("/api/swaps/{$swap->id}");

        $response->assertStatus(401);
    }

    public function test_a_non_pending_swap_cannot_be_confirmed(): void
    {
        $userA = User::factory()->create();
        $artistA = Artist::factory()->create(['user_id' => $userA->id]);

        $swap = Swap::factory()->create([
            'artist_a_id' => $artistA->id,
            'status' => 'rejected',
        ]);

        Passport::actingAs($userA);

        $response = $this->putJson("/api/swaps/{$swap->id}");

        $response->assertStatus(422);
    }

    public function test_confirming_a_nonexistent_swap_returns_404(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->putJson('/api/swaps/99999');

        $response->assertStatus(404);
    }
}