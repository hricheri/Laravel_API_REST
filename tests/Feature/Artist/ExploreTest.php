<?php

namespace Tests\Feature\Artist;

use App\Models\Artist;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ExploreTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_artist_can_explore_other_artists(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->create(['user_id' => $user->id]);

        Artist::factory()->count(3)->create();

        Passport::actingAs($user);

        $response = $this->getJson('/api/artists');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'artists');

        $ids = collect($response->json('artists'))->pluck('id');
        $this->assertFalse($ids->contains($myArtist->id));
    }

    public function test_an_artist_can_filter_explore_by_city(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        Artist::factory()->create(['city' => 'Barcelona']);
        Artist::factory()->create(['city' => 'Berlin']);
        Artist::factory()->create(['city' => 'Barcelona']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/artists?filter=city&city=Barcelona');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'artists');
    }

    public function test_an_artist_does_not_see_artists_they_already_liked(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->create(['user_id' => $user->id]);

        $likedArtist = Artist::factory()->create();
        $notLikedArtist = Artist::factory()->create();

        Like::factory()->create([
            'liker_artist_id' => $myArtist->id,
            'liked_artist_id' => $likedArtist->id,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/artists');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'artists');

        $ids = collect($response->json('artists'))->pluck('id');
        $this->assertFalse($ids->contains($likedArtist->id));
        $this->assertTrue($ids->contains($notLikedArtist->id));
    }

    public function test_exploring_artists_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/artists');

        $response->assertStatus(401);
    }
}