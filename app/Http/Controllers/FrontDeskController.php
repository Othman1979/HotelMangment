<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Company;
use App\Models\HotelSetting;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Services\Availability;
use App\Services\FrontDesk;
use Illuminate\Http\Request;

class FrontDeskController extends Controller
{
    public function __construct(private FrontDesk $frontDesk, private Availability $availability) {}

    public function index(string $list)
    {
        $today = HotelSetting::businessDate();
        $stays = ReservationRoom::query()->with(['reservation.guest', 'reservation.company', 'room', 'roomType', 'account'])
            ->when($list === 'arrivals', fn ($q) => $q->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::Tentative->value])->whereDate('arrival_date', '<=', $today))
            ->when($list === 'in-house', fn ($q) => $q->where('status', ReservationStatus::CheckedIn->value))
            ->when($list === 'departures', fn ($q) => $q->where('status', ReservationStatus::CheckedIn->value)->whereDate('departure_date', '<=', $today))
            ->orderBy('arrival_date')->get();

        return view('front.index', [
            'list' => $list,
            'stays' => $stays,
            'today' => $today,
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function checkInForm(ReservationRoom $stay)
    {
        $stay->load(['reservation.guest', 'roomType', 'room']);
        $rooms = $this->availability->freeRooms($stay->room_type_id, $stay->arrival_date, $stay->departure_date, $stay->id);

        return view('front.check-in', ['stay' => $stay, 'rooms' => $rooms, 'today' => HotelSetting::businessDate()]);
    }

    public function checkIn(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['room_id' => ['required', 'exists:rooms,id']]);
        $account = $this->frontDesk->checkIn($stay, Room::findOrFail($data['room_id']));

        return redirect()->route('accounts.show', $account)->with('ok', 'Guest checked in.');
    }

    public function checkOut(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['company_id' => ['nullable', 'exists:companies,id']]);
        $invoice = $this->frontDesk->checkOut($stay, isset($data['company_id']) ? Company::find($data['company_id']) : null);

        return redirect()->route('invoices.show', $invoice)->with('ok', 'Guest checked out.');
    }

    public function move(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['room_id' => ['required', 'exists:rooms,id'], 'reason' => ['required', 'string', 'max:255']]);
        $this->frontDesk->moveRoom($stay, Room::findOrFail($data['room_id']), $data['reason']);

        return back()->with('ok', 'Saved successfully.');
    }
}
