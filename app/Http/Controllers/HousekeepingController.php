<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ServiceStatus;
use App\Models\AuditTrail;
use App\Models\HotelSetting;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Services\Availability;
use App\Services\HotelException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter');
        $rooms = Room::query()->with(['roomType', 'blocks' => fn ($q) => $q->whereNull('released_at')->orderBy('from_date')])
            ->where('is_active', true)
            ->when($filter === 'dirty', fn ($q) => $q->where('housekeeping_status', HousekeepingStatus::Dirty->value))
            ->when($filter === 'ooo', fn ($q) => $q->where('service_status', '!=', ServiceStatus::InService->value))
            ->when($filter === 'occupied', fn ($q) => $q->where('occupancy_status', OccupancyStatus::Occupied->value))
            ->orderBy('floor')->orderBy('room_number')->get();

        return view('housekeeping.index', ['rooms' => $rooms, 'filter' => $filter, 'today' => HotelSetting::businessDate()]);
    }

    public function status(Request $request, Room $room)
    {
        $data = $request->validate(['status' => ['required', Rule::enum(HousekeepingStatus::class)]]);
        $old = $room->housekeeping_status->value;
        $room->update(['housekeeping_status' => $data['status']]);
        AuditTrail::record('room.housekeeping', $room, ['status' => $old], ['status' => $data['status']]);

        return back()->with('ok', 'Room :room is now :status.')->with('ok_replace', ['room' => $room->room_number, 'status' => $room->housekeeping_status->label()]);
    }

    public function block(Request $request, Room $room, Availability $availability)
    {
        $today = HotelSetting::businessDate();
        $data = $request->validate([
            'type' => ['required', Rule::in([ServiceStatus::OutOfOrder->value, ServiceStatus::OutOfService->value])],
            'from_date' => ['required', 'date', 'after_or_equal:'.$today->toDateString()],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $from = CarbonImmutable::parse($data['from_date']);
        $to = CarbonImmutable::parse($data['to_date']);
        if ($availability->conflictingStays($room->id, $from, $to->addDay())->exists()) {
            throw new HotelException('Room :room has reservations in this period.', ['room' => $room->room_number]);
        }
        $block = $room->blocks()->create($data + ['user_id' => $request->user()->id]);
        if ($from->lte($today)) {
            $room->update(['service_status' => $data['type']]);
        }
        AuditTrail::record('room.block', $room, null, $block->only(['type', 'from_date', 'to_date', 'reason']));

        return back()->with('ok', 'Saved successfully.');
    }

    public function release(RoomBlock $block)
    {
        $block->update(['released_at' => now()]);
        $room = $block->room;
        if (! $room->blocks()->whereNull('released_at')->whereDate('from_date', '<=', HotelSetting::businessDate())->exists()) {
            $room->update(['service_status' => ServiceStatus::InService]);
        }
        AuditTrail::record('room.release', $room, null, ['block' => $block->id]);

        return back()->with('ok', 'Saved successfully.');
    }
}
