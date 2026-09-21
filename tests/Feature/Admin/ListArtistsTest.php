<?php

namespace Tests\Feature\Admin;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ListArtistsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_list_all_artists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Artist::factory()->count(3)->create();

        Passport::actingAs($admin);

        $response = $this->getJson('/api/artists');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'artists');
    }

    public function test_listing_all_artists_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/artists');

        $response->assertStatus(401);
    }
}