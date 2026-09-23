<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Like;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $artists = Artist::with('user')->get();

            return response()->json(['artists' => $artists], 200);
        }

        return $this->explore($request);
    }

    private function explore(Request $request)
    {
        $myArtist = $request->user()->artist;

        $likedIds = Like::where('liker_artist_id', $myArtist->id)->pluck('liked_artist_id')->toArray();
        $excludedIds = array_merge($likedIds, [$myArtist->id]);

        $query = Artist::with('user')->whereNotIn('id', $excludedIds);

        $filter = $request->query('filter', 'all');

        if ($filter === 'city' && $request->filled('city')) {
            $query->where('city', $request->query('city'));
        }

        $artists = $query->get();

        return response()->json(['artists' => $artists], 200);
    }

    public function verify(Request $request, Artist $artist)
    {
        $validated = $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $artist->update(['is_verified' => $validated['is_verified']]);

        return response()->json(['artist' => $artist->fresh()], 200);
    }

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
            'city' => 'sometimes|nullable|string|max:255',
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

        if (array_key_exists('city', $validated)) {
            $artistData['city'] = $validated['city'];
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