<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reserved_on',
        'start_time',
        'end_time',
        'consumables',
        'rental_fee',
        'consumables_fee',
        'tax_amount',
        'total_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reserved_on' => 'date',
            'consumables' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        return $query->where(function (Builder $query) use ($today, $nowTime) {
            $query->whereDate('reserved_on', '>', $today)
                ->orWhere(function (Builder $query) use ($today, $nowTime) {
                    $query->whereDate('reserved_on', $today)
                        ->whereTime('end_time', '>', $nowTime);
                });
        });
    }

    public function isUpcoming(): bool
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        if ($this->reserved_on->toDateString() > $today) {
            return true;
        }

        if ($this->reserved_on->toDateString() === $today) {
            return (string) $this->end_time > $nowTime;
        }

        return false;
    }
}
