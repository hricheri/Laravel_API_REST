<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function index(Request $request)
    {
        $myArtist = $request->user()->artist;

        $likes = Like::with('liked.user')
            ->where('liker_artist_id', $myArtist->id)
            ->get()
            ->map(function (Like $like) use ($myArtist) {
                return [
                    'id' => $like->id,
                    'liked_artist_id' => $like->liked_artist_id,
                    'artist' => $like->liked,
                    'is_match' => Like::isMutual($myArtist->id, $like->liked_artist_id),
                ];
            });

        return response()->json(['favorites' => $likes], 200);
    }

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