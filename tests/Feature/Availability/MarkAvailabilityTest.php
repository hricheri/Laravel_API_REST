<?php

namespace Tests\Feature\Availability;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MarkAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_artist_can_mark_a_single_day_available(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/artists/{$artist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('availabilities', [
            'artist_id' => $artist->id,
            'date' => '2026-10-15',
        ]);
    }

    public function test_an_artist_can_mark_multiple_days_available(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/artists/{$artist->id}/availabilities", [
            'dates' => ['2026-10-15', '2026-10-16', '2026-10-17'],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('availabilities', 3);
        $this->assertDatabaseHas('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-15']);
        $this->assertDatabaseHas('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-16']);
        $this->assertDatabaseHas('availabilities', ['artist_id' => $artist->id, 'date' => '2026-10-17']);
    }

    public function test_marking_an_already_marked_day_does_not_create_a_duplicate(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/artists/{$artist->id}/availabilities", ['date' => '2026-10-15']);
        $response = $this->postJson("/api/artists/{$artist->id}/availabilities", ['date' => '2026-10-15']);

        $response->assertStatus(201);
        $this->assertDatabaseCount('availabilities', 1);
    }

    public function test_an_artist_cannot_mark_availability_for_another_artist(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);
        $otherArtist = Artist::factory()->create();

        Passport::actingAs($user);

        $response = $this->postJson("/api/artists/{$otherArtist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_marking_availability_fails_without_authentication(): void
    {
        $artist = Artist::factory()->create();

        $response = $this->postJson("/api/artists/{$artist->id}/availabilities", [
            'date' => '2026-10-15',
        ]);

        $response->assertStatus(401);
    }

    public function test_marking_availability_fails_without_date_or_dates(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/artists/{$artist->id}/availabilities", []);

        $response->assertStatus(422);
    }
}