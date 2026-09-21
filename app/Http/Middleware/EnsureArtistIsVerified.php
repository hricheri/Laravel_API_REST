<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArtistIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $artist = $request->user()?->artist;

        if (! $artist || ! $artist->is_verified) {
            return response()->json(['message' => 'Your artist profile must be verified to access this resource.'], 403);
        }

        return $next($request);
    }
}