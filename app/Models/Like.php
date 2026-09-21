<?php

namespace App\Models;

use Database\Factories\LikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    /** @use HasFactory<LikeFactory> */
    use HasFactory;

    protected $fillable = [
        'liker_artist_id',
        'liked_artist_id',
    ];

    public function liker(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'liker_artist_id');
    }

    public function liked(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'liked_artist_id');
    }

    /**
     * Whether liker and liked have mutually liked each other (a match).
     */
    public static function isMutual(int $artistIdA, int $artistIdB): bool
    {
        return self::where('liker_artist_id', $artistIdA)->where('liked_artist_id', $artistIdB)->exists()
            && self::where('liker_artist_id', $artistIdB)->where('liked_artist_id', $artistIdA)->exists();
    }
}