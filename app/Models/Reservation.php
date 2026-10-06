<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    public const SOURCES = ['walk_in', 'phone', 'website', 'ota', 'company', 'travel_agent'];

    protected $fillable = ['reservation_no', 'guest_id', 'company_id', 'source', 'arrival_date', 'departure_date', 'status', 'external_ref', 'notes', 'cancelled_at', 'cancel_reason', 'created_by'];

    protected function casts(): array
    {
        return [
            'arrival_date' => DateOnly::class,
            'departure_date' => DateOnly::class,
            'status' => ReservationStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function nights(): int
    {
        return (int) $this->arrival_date->diffInDays($this->departure_date);
    }

    /** Keeps the header status in line with its room lines. */
    public function syncStatus(): void
    {
        $statuses = $this->rooms()->pluck('status')->map(fn ($s) => $s instanceof ReservationStatus ? $s : ReservationStatus::from($s));
        $pick = fn (ReservationStatus ...$order) => collect($order)->first(fn ($s) => $statuses->contains($s));
        $status = $pick(ReservationStatus::CheckedIn, ReservationStatus::Confirmed, ReservationStatus::Tentative, ReservationStatus::CheckedOut, ReservationStatus::NoShow, ReservationStatus::Cancelled);
        if ($status && $status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
