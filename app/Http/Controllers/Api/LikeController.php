<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'liked_artist_id' => 'required|integer|exists:artists,id',
        ]);

        $myArtist = $request->user()->artist;

        $like = Like::firstOrCreate([
            'liker_artist_id' => $myArtist->id,
            'liked_artist_id' => $validated['liked_artist_id'],
        ]);

        return response()->json(['like' => $like], 201);
    }
}