<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'artist' => $user->artist,
        ], 200);
    }

    public function updateMe(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'profile_photo' => 'sometimes|image|max:5120',
        ]);

        $user = $request->user();

        if (array_key_exists('name', $validated)) {
            $user->update(['name' => $validated['name']]);
        }

        $artistData = [];

        if (array_key_exists('bio', $validated)) {
            $artistData['bio'] = $validated['bio'];
        }

        if ($request->hasFile('profile_photo')) {
            $artistData['profile_photo'] = $request->file('profile_photo')->store('artists', 'public');
        }

        if (! empty($artistData)) {
            $user->artist->update($artistData);
        }

        return response()->json([
            'user' => $user->fresh(),
            'artist' => $user->artist->fresh(),
        ], 200);
    }
}