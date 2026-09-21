<?php

namespace Tests\Feature\Admin;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class VerifyArtistTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_verify_an_artist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $artist = Artist::factory()->create(['is_verified' => false]);

        Passport::actingAs($admin);

        $response = $this->putJson("/api/artists/{$artist->id}", [
            'is_verified' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'artist' => ['id' => $artist->id, 'is_verified' => true],
            ]);

        $this->assertDatabaseHas('artists', [
            'id' => $artist->id,
            'is_verified' => true,
        ]);
    }

    public function test_a_regular_artist_cannot_verify_another_artist(): void
    {
        $user = User::factory()->create(['role' => 'artist']);
        Artist::factory()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->create(['is_verified' => false]);

        Passport::actingAs($user);

        $response = $this->putJson("/api/artists/{$otherArtist->id}", [
            'is_verified' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_verifying_an_artist_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->putJson("/api/artists/{$artist->id}", [
            'is_verified' => true,
        ]);

        $response->assertStatus(401);
    }

    public function test_verifying_a_nonexistent_artist_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Passport::actingAs($admin);

        $response = $this->putJson('/api/artists/99999', [
            'is_verified' => true,
        ]);

        $response->assertStatus(404);
    }
}