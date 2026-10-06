<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\HotelSetting;
use App\Models\ReservationRoom;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class RoomRackController extends Controller
{
    public function __invoke(Request $request)
    {
        $from = $request->filled('from') ? CarbonImmutable::parse($request->date('from')) : HotelSetting::businessDate();
        $days = 14;
        $to = $from->addDays($days);
        $rooms = Room::query()->with('roomType')->where('is_active', true)->orderBy('room_number')->get();
        $stays = ReservationRoom::query()->with('reservation.guest')
            ->whereNotNull('room_id')
            ->whereIn('status', [...ReservationStatus::active(), ReservationStatus::CheckedOut->value])
            ->where('arrival_date', '<', $to->toDateString())
            ->where('departure_date', '>', $from->toDateString())
            ->get()->groupBy('room_id');
        $unassigned = ReservationRoom::query()->with(['reservation.guest', 'roomType'])
            ->whereNull('room_id')->whereIn('status', ReservationStatus::active())
            ->where('arrival_date', '<', $to->toDateString())->where('departure_date', '>', $from->toDateString())
            ->orderBy('arrival_date')->get();

        return view('rack', compact('from', 'days', 'rooms', 'stays', 'unassigned'));
    }
}
