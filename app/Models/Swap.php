<?php

namespace App\Models;

use Database\Factories\SwapFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Swap extends Model
{
    /** @use HasFactory<SwapFactory> */
    use HasFactory;

    protected $fillable = [
        'artist_a_id',
        'artist_b_id',
        'start_date',
        'end_date',
        'status',
        'confirmed_by_a',
        'confirmed_by_b',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'confirmed_by_a' => 'boolean',
        'confirmed_by_b' => 'boolean',
    ];

    public function artistA(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_a_id');
    }

    public function artistB(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_b_id');
    }

    /**
     * Calculate the overlapping availability dates between two artists.
     * Returns [startDate, endDate] as the first and last day of the
     * intersection (sorted), or [null, null] if there's no overlap.
     */
    public static function calculateOverlap(int $artistIdA, int $artistIdB): array
    {
        $datesA = Availability::where('artist_id', $artistIdA)
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        $datesB = Availability::where('artist_id', $artistIdB)
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        $overlap = array_values(array_intersect($datesA, $datesB));
        sort($overlap);

        if (empty($overlap)) {
            return [null, null];
        }

        return [$overlap[0], $overlap[count($overlap) - 1]];
    }

    public function myConfirmed(int $artistId): bool
    {
        if ($this->artist_a_id === $artistId) {
            return $this->confirmed_by_a;
        }

        if ($this->artist_b_id === $artistId) {
            return $this->confirmed_by_b;
        }

        return false;
    }

    public function isParticipant(int $artistId): bool
    {
        return $this->artist_a_id === $artistId || $this->artist_b_id === $artistId;
    }
}