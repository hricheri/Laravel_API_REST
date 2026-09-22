<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Swap;
use Illuminate\Http\Request;

class SwapController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'artist_id' => 'required|integer|exists:artists,id',
        ]);

        $myArtist = $request->user()->artist;
        $otherArtistId = $validated['artist_id'];

        if (! Like::isMutual($myArtist->id, $otherArtistId)) {
            return response()->json(['message' => 'You can only start a swap with a mutual match.'], 403);
        }

        [$startDate, $endDate] = Swap::calculateOverlap($myArtist->id, $otherArtistId);

        $swap = Swap::create([
            'artist_a_id' => $myArtist->id,
            'artist_b_id' => $otherArtistId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending',
        ]);

        return response()->json(['swap' => $swap], 201);
    }
}