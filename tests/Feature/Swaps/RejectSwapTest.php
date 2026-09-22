<?php

namespace Tests\Feature\Swaps;

use App\Models\Artist;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RejectSwapTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pending_swap_can_be_rejected(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        $swap = Swap::factory()->create([
            'artist_a_id' => $artist->id,
            'status' => 'pending',
        ]);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/swaps/{$swap->id}/reject");

        $response->assertStatus(200)
            ->assertJson(['swap' => ['status' => 'rejected']]);

        $this->assertDatabaseHas('swaps', ['id' => $swap->id, 'status' => 'rejected']);
    }

    public function test_a_confirmed_swap_is_cancelled_instead_of_rejected(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        $swap = Swap::factory()->create([
            'artist_a_id' => $artist->id,
            'status' => 'confirmed',
            'confirmed_by_a' => true,
            'confirmed_by_b' => true,
        ]);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/swaps/{$swap->id}/reject");

        $response->assertStatus(200)
            ->assertJson(['swap' => ['status' => 'cancelled']]);

        $this->assertDatabaseHas('swaps', ['id' => $swap->id, 'status' => 'cancelled']);
    }

    public function test_an_artist_not_part_of_the_swap_cannot_reject_it(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        $swap = Swap::factory()->create(['status' => 'pending']);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/swaps/{$swap->id}/reject");

        $response->assertStatus(403);
    }

    public function test_rejecting_a_swap_fails_without_authentication(): void
    {
        $swap = Swap::factory()->create();

        $response = $this->deleteJson("/api/swaps/{$swap->id}/reject");

        $response->assertStatus(401);
    }

    public function test_an_already_rejected_swap_cannot_be_rejected_again(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        $swap = Swap::factory()->create([
            'artist_a_id' => $artist->id,
            'status' => 'rejected',
        ]);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/swaps/{$swap->id}/reject");

        $response->assertStatus(422);
    }

    public function test_rejecting_a_nonexistent_swap_returns_404(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->deleteJson('/api/swaps/99999/reject');

        $response->assertStatus(404);
    }
}