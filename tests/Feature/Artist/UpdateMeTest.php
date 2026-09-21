<?php

namespace Tests\Feature\Artist;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdateMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_update_their_bio(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id, 'bio' => 'Old bio']);

        Passport::actingAs($user);

        $response = $this->putJson('/api/me', [
            'bio' => 'New bio about my tattoo style.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'artist' => ['bio' => 'New bio about my tattoo style.'],
            ]);

        $this->assertDatabaseHas('artists', [
            'user_id' => $user->id,
            'bio' => 'New bio about my tattoo style.',
        ]);
    }

    public function test_an_authenticated_user_can_update_their_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->putJson('/api/me', [
            'profile_photo' => UploadedFile::fake()->image('new-photo.jpg'),
        ]);

        $response->assertStatus(200);

        $artist = $user->fresh()->artist;
        $this->assertNotNull($artist->profile_photo);
        Storage::disk('public')->assertExists($artist->profile_photo);
    }

    public function test_an_authenticated_user_can_update_their_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->putJson('/api/me', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => ['name' => 'New Name'],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_partial_update_does_not_erase_other_fields(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        Artist::factory()->create(['user_id' => $user->id, 'bio' => 'Existing bio']);

        Passport::actingAs($user);

        $response = $this->putJson('/api/me', [
            'name' => 'Jane Updated',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('artists', [
            'user_id' => $user->id,
            'bio' => 'Existing bio',
        ]);
    }

    public function test_updating_profile_fails_without_authentication(): void
    {
        $response = $this->putJson('/api/me', ['bio' => 'New bio']);

        $response->assertStatus(401);
    }

    public function test_updating_profile_fails_with_invalid_photo(): void
    {
        $user = User::factory()->create();
        Artist::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->putJson('/api/me', [
            'profile_photo' => UploadedFile::fake()->create('document.txt', 10),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_photo']);
    }
}