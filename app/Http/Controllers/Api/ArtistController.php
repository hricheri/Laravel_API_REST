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
}