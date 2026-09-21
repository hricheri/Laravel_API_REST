<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Availability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
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
}