<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\ServiceStatus;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Availability
{
    /** Stays that hold the room for any night in [from, to). */
    public function conflictingStays(int $roomId, CarbonImmutable $from, CarbonImmutable $to, ?int $exceptStayId = null): Builder
    {
        return ReservationRoom::query()
            ->where('room_id', $roomId)
            ->whereIn('status', ReservationStatus::active())
            ->where('arrival_date', '<', $to->toDateString())
            ->where('departure_date', '>', $from->toDateString())
            ->when($exceptStayId, fn ($q) => $q->whereKeyNot($exceptStayId));
    }

    public function isBlocked(int $roomId, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        return RoomBlock::query()
            ->where('room_id', $roomId)
            ->whereNull('released_at')
            ->where('from_date', '<', $to->toDateString())
            ->where('to_date', '>=', $from->toDateString())
            ->exists();
    }

    public function isRoomFree(Room $room, CarbonImmutable $from, CarbonImmutable $to, ?int $exceptStayId = null): bool
    {
        return $room->is_active
            && ! $this->conflictingStays($room->id, $from, $to, $exceptStayId)->exists()
            && ! $this->isBlocked($room->id, $from, $to);
    }

    /** @return Collection<int, Room> */
    public function freeRooms(?int $roomTypeId, CarbonImmutable $from, CarbonImmutable $to, ?int $exceptStayId = null): Collection
    {
        $busy = ReservationRoom::query()
            ->whereNotNull('room_id')
            ->whereIn('status', ReservationStatus::active())
            ->where('arrival_date', '<', $to->toDateString())
            ->where('departure_date', '>', $from->toDateString())
            ->when($exceptStayId, fn ($q) => $q->whereKeyNot($exceptStayId))
            ->pluck('room_id');
        $blocked = RoomBlock::query()
            ->whereNull('released_at')
            ->where('from_date', '<', $to->toDateString())
            ->where('to_date', '>=', $from->toDateString())
            ->pluck('room_id');

        return Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->whereNotIn('id', $busy->merge($blocked)->unique())
            ->orderBy('room_number')
            ->get();
    }

    /**
     * Rooms of the type still sellable on every night of [from, to) — counts unassigned stays too.
     */
    public function availableCount(RoomType $type, CarbonImmutable $from, CarbonImmutable $to, ?int $exceptStayId = null): int
    {
        $rooms = Room::query()->where('room_type_id', $type->id)->where('is_active', true)->pluck('id');
        $stays = ReservationRoom::query()
            ->where('room_type_id', $type->id)
            ->whereIn('status', ReservationStatus::active())
            ->where('arrival_date', '<', $to->toDateString())
            ->where('departure_date', '>', $from->toDateString())
            ->when($exceptStayId, fn ($q) => $q->whereKeyNot($exceptStayId))
            ->get(['arrival_date', 'departure_date']);
        $blocks = RoomBlock::query()
            ->whereIn('room_id', $rooms)
            ->whereNull('released_at')
            ->where('from_date', '<', $to->toDateString())
            ->where('to_date', '>=', $from->toDateString())
            ->get(['room_id', 'from_date', 'to_date']);

        $min = $rooms->count();
        foreach (CarbonPeriod::create($from, $to->subDay()) as $night) {
            $n = CarbonImmutable::parse($night);
            $sold = $stays->filter(fn ($s) => $s->arrival_date->lte($n) && $s->departure_date->gt($n))->count();
            $out = $blocks->filter(fn ($b) => $b->from_date->lte($n) && $b->to_date->gte($n))->pluck('room_id')->unique()->count();
            $min = min($min, $rooms->count() - $sold - $out);
        }

        return max(0, $min);
    }

    /** @return Collection<int, array{type: RoomType, available: int}> */
    public function summary(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return RoomType::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn (RoomType $t) => ['type' => $t, 'available' => $this->availableCount($t, $from, $to)]);
    }

    public function isOutOfOrderToday(Room $room): bool
    {
        return $room->service_status !== ServiceStatus::InService;
    }
}
