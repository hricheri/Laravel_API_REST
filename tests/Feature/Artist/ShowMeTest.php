<?php

namespace Tests\Feature\Artist;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShowMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_view_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $artist = Artist::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Traveling tattoo artist.',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => ['id' => $user->id, 'name' => 'Jane Doe'],
                'artist' => ['id' => $artist->id, 'bio' => 'Traveling tattoo artist.'],
            ]);
    }

    public function test_viewing_own_profile_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}