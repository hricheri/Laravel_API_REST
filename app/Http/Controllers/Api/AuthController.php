<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'bio' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|max:5120',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'artist',
        ]);

        $profilePhotoPath = $request->hasFile('profile_photo')
            ? $request->file('profile_photo')->store('artists', 'public')
            : null;

        $artist = Artist::create([
            'user_id' => $user->id,
            'bio' => $validated['bio'] ?? null,
            'city' => $validated['city'] ?? null,
            'profile_photo' => $profilePhotoPath,
        ]);

        $token = $user->createToken('auth-token')->accessToken;

        $response = [
            'user' => $user,
            'artist' => $artist,
            'token' => $token,
        ];

        if (empty($validated['bio']) && ! $profilePhotoPath) {
            $response['message'] = 'Registration successful! Complete your profile to unlock all features.';
        }

        return response()->json($response, 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth-token')->accessToken;

        return response()->json([
            'user' => $user,
            'artist' => $user->artist,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }
}