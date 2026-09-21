<?php

namespace Tests\Feature\Availability;

use App\Models\Artist;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeleteAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_artist_can_delete_a_single_day(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);
        Availability::factory()->create(['artist_id' => $artist->id, 'date' => '2026-10-15']);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/artists/{$artist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('availabilities', [
            'artist_id' => $artist->id,
            'date' => '2026-10-15',
        ]);
    }

    public function test_an_artist_can_delete_multiple_days(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);
        Availability::factory()->create(['artist_id' => $artist->id, 'date' => '2026-10-15']);
        Availability::factory()->create(['artist_id' => $artist->id, 'date' => '2026-10-16']);
        Availability::factory()->create(['artist_id' => $artist->id, 'date' => '2026-10-17']);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/artists/{$artist->id}/availabilities", [
            'dates' => ['2026-10-15', '2026-10-16'],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-15']);
        $this->assertDatabaseMissing('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-16']);
        $this->assertDatabaseHas('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-17']);
    }

    public function test_deleting_a_nonexistent_day_does_not_fail(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/artists/{$artist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(200);
    }

    public function test_an_artist_cannot_delete_availability_for_another_artist(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->create();
        Availability::factory()->create(['artist_id' => $otherArtist->id, 'date' => '2026-10-15']);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/artists/{$otherArtist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('availabilities', [
            'artist_id' => $otherArtist->id,
            'date' => '2026-10-15',
        ]);
    }

    public function test_deleting_availability_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->deleteJson("/api/artists/{$artist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(401);
    }

    public function test_deleting_availability_fails_without_date_or_dates(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->deleteJson("/api/artists/{$artist->id}/availabilities", []);

        $response->assertStatus(422);
    }
}