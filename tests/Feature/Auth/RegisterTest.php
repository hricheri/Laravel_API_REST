<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_with_full_profile_data(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'bio' => 'Traveling tattoo artist.',
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'artist' => ['id', 'bio', 'profile_photo'],
                'token',
            ])
            ->assertJsonMissing(['message']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('artists', ['bio' => 'Traveling tattoo artist.']);
    }

    public function test_a_user_can_register_without_bio_or_photo(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'artist' => ['id', 'bio', 'profile_photo'],
                'token',
                'message',
            ])
            ->assertJson([
                'message' => 'Registration successful! Complete your profile to unlock all features.',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_when_passwords_do_not_match(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_invalid_profile_photo(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'email' => 'someone2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile_photo' => UploadedFile::fake()->create('document.txt', 10),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_photo']);
    }
}