<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Availability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, Artist $artist)
    {
        $isOwnProfile = $artist->user_id === $request->user()->id;

        if (! $isOwnProfile) {
            $myArtist = $request->user()->artist;

            if (! $myArtist || ! $myArtist->is_verified) {
                return response()->json(['message' => 'Your artist profile must be verified to access this resource.'], 403);
            }
        }

        return response()->json([
            'availabilities' => $artist->availabilities()->orderBy('date')->get(),
        ], 200);
    }

    public function store(Request $request, Artist $artist)
    {
        if ($artist->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'date' => 'required_without:dates|date_format:Y-m-d',
            'dates' => 'required_without:date|array|min:1',
            'dates.*' => 'date_format:Y-m-d',
        ]);

        $dates = $validated['dates'] ?? [$validated['date']];

        foreach ($dates as $date) {
            Availability::firstOrCreate([
                'artist_id' => $artist->id,
                'date' => $date,
            ]);
        }

        return response()->json([
            'availabilities' => $artist->availabilities()->orderBy('date')->get(),
        ], 201);
    }

    public function destroy(Request $request, Artist $artist)
    {
        if ($artist->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'date' => 'required_without:dates|date_format:Y-m-d',
            'dates' => 'required_without:date|array|min:1',
            'dates.*' => 'date_format:Y-m-d',
        ]);

        $dates = $validated['dates'] ?? [$validated['date']];

        Availability::where('artist_id', $artist->id)
            ->whereIn('date', $dates)
            ->delete();

        return response()->json([
            'availabilities' => $artist->availabilities()->orderBy('date')->get(),
        ], 200);
    }
}