<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ReservationStatus;
use App\Enums\ServiceStatus;
use App\Models\GuestAccount;
use App\Models\HotelSetting;
use App\Models\NightAudit;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Services\Availability;

class DashboardController extends Controller
{
    public function __invoke(Availability $availability)
    {
        $today = HotelSetting::businessDate();
        $rooms = Room::query()->where('is_active', true)->get();
        $stays = ReservationRoom::query();

        return view('dashboard', [
            'today' => $today,
            'hotel' => HotelSetting::current(),
            'typeTotals' => $rooms->countBy('room_type_id'),
            'stats' => [
                'rooms' => $rooms->count(),
                'occupied' => $rooms->where('occupancy_status', OccupancyStatus::Occupied)->count(),
                'vacant_ready' => $rooms->filter->isReadyForCheckIn()->count(),
                'dirty' => $rooms->where('housekeeping_status', HousekeepingStatus::Dirty)->count(),
                'ooo' => $rooms->where('service_status', '!=', ServiceStatus::InService)->count(),
                'arrivals' => (clone $stays)->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::Tentative->value])->whereDate('arrival_date', $today)->count(),
                'departures' => (clone $stays)->where('status', ReservationStatus::CheckedIn->value)->whereDate('departure_date', '<=', $today)->count(),
                'in_house' => (clone $stays)->where('status', ReservationStatus::CheckedIn->value)->count(),
                'open_balance' => GuestAccount::query()->where('status', 'open')->sum('balance'),
            ],
            'arrivalsList' => (clone $stays)->with(['reservation.guest', 'room', 'roomType'])->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::Tentative->value])->whereDate('arrival_date', $today)->orderBy('id')->limit(6)->get(),
            'departuresList' => (clone $stays)->with(['reservation.guest', 'room', 'account'])->where('status', ReservationStatus::CheckedIn->value)->whereDate('departure_date', '<=', $today)->orderBy('id')->limit(6)->get(),
            'availability' => $availability->summary($today, $today->addDay()),
            'lastAudit' => NightAudit::query()->latest('business_date')->first(),
            'shift' => auth()->user()->openShift(),
        ]);
    }
}
