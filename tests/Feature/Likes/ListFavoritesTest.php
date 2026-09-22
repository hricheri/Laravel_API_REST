<?php

namespace Tests\Feature\Likes;

use App\Models\Artist;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_artist_can_list_their_favorites(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);

        $likedArtist1 = Artist::factory()->create();
        $likedArtist2 = Artist::factory()->create();

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $likedArtist1->id]);
        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $likedArtist2->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'favorites');
    }

    public function test_favorites_correctly_flag_mutual_matches(): void
    {
        $user = User::factory()->create();
        $myArtist = Artist::factory()->verified()->create(['user_id' => $user->id]);

        $matchedArtist = Artist::factory()->create();
        $unmatchedArtist = Artist::factory()->create();

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $matchedArtist->id]);
        Like::factory()->create(['liker_artist_id' => $matchedArtist->id, 'liked_artist_id' => $myArtist->id]);

        Like::factory()->create(['liker_artist_id' => $myArtist->id, 'liked_artist_id' => $unmatchedArtist->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/favorites');

        $response->assertStatus(200);

        $favorites = collect($response->json('favorites'));

        $matched = $favorites->firstWhere('liked_artist_id', $matchedArtist->id);
        $unmatched = $favorites->firstWhere('liked_artist_id', $unmatchedArtist->id);

        $this->assertTrue($matched['is_match']);
        $this->assertFalse($unmatched['is_match']);
    }

    public function test_favorites_returns_empty_array_when_no_likes_given(): void
    {
        $user = User::factory()->create();
        Artist::factory()->verified()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'favorites');
    }

    public function test_listing_favorites_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/favorites');

        $response->assertStatus(401);
    }
}