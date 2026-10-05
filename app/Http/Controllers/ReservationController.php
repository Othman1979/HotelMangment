<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\Guest;
use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Availability;
use App\Services\FrontDesk;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function __construct(private FrontDesk $frontDesk, private Availability $availability) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $status = $request->input('status');
        $reservations = Reservation::query()->with(['guest', 'rooms.room', 'company'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('reservation_no', 'like', "%{$q}%")
                ->orWhereHas('guest', fn ($g) => $g->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('arrival_date', '<=', $request->date('date'))->whereDate('departure_date', '>', $request->date('date')))
            ->latest('id')->paginate(25)->withQueryString();

        return view('reservations.index', compact('reservations', 'q', 'status'));
    }

    public function create(Request $request)
    {
        $today = HotelSetting::businessDate();

        return view('reservations.create', [
            'today' => $today,
            'walkIn' => $request->boolean('walk_in'),
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_number')->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(),
            'guest' => $request->filled('guest_id') ? Guest::find($request->integer('guest_id')) : null,
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after:from'], 'room_type_id' => ['nullable', 'integer']]);
        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);

        return [
            'types' => $this->availability->summary($from, $to)->map(fn ($r) => ['id' => $r['type']->id, 'name' => $r['type']->name(), 'rate' => $r['type']->base_rate, 'available' => $r['available']])->values(),
            'rooms' => $this->availability->freeRooms($data['room_type_id'] ?? null, $from, $to)->map(fn ($r) => ['id' => $r->id, 'number' => $r->room_number, 'type_id' => $r->room_type_id, 'status' => $r->statusCode()])->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'guest_id' => ['nullable', 'required_without:guest.full_name', 'exists:guests,id'],
            'guest.full_name' => ['nullable', 'required_without:guest_id', 'string', 'max:150'],
            'guest.phone' => ['nullable', 'string', 'max:50'],
            'guest.nationality' => ['nullable', 'string', 'max:60'],
            'guest.id_type' => ['nullable', 'in:national_id,passport,residency,other'],
            'guest.id_number' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'source' => ['required', Rule::in(Reservation::SOURCES)],
            'status' => ['required', Rule::in([ReservationStatus::Confirmed->value, ReservationStatus::Tentative->value])],
            'arrival_date' => ['required', 'date'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'external_ref' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rooms' => ['required', 'array', 'min:1', 'max:20'],
            'rooms.*.room_type_id' => ['required', 'exists:room_types,id'],
            'rooms.*.room_id' => ['nullable', 'exists:rooms,id'],
            'rooms.*.adults' => ['required', 'integer', 'min:1', 'max:10'],
            'rooms.*.children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rooms.*.nightly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        $guestId = $data['guest_id'] ?? Guest::create(array_filter($data['guest'], fn ($v) => $v !== null))->id;
        $reservation = $this->frontDesk->book([
            'guest_id' => $guestId,
            'company_id' => $data['company_id'] ?? null,
            'source' => $data['source'],
            'status' => $data['status'],
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'external_ref' => $data['external_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], array_values($data['rooms']));

        if ($request->boolean('check_in_now')) {
            $stay = $reservation->rooms()->first();

            return redirect()->route('stays.check-in', $stay);
        }

        return redirect()->route('reservations.show', $reservation)->with('ok', 'Reservation :no created.')->with('ok_replace', ['no' => $reservation->reservation_no]);
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['guest', 'company', 'creator', 'rooms.room', 'rooms.roomType', 'rooms.account']);
        $freeRooms = $reservation->rooms->mapWithKeys(fn (ReservationRoom $s) => [
            $s->id => in_array($s->status->value, ReservationStatus::active(), true) && $s->status !== ReservationStatus::CheckedIn
                ? $this->availability->freeRooms($s->room_type_id, $s->arrival_date, $s->departure_date, $s->id) : collect(),
        ]);

        return view('reservations.show', [
            'reservation' => $reservation,
            'freeRooms' => $freeRooms,
            'today' => HotelSetting::businessDate(),
            'history' => AuditTrail::query()->with('user')->where('entity_type', 'Reservation')->where('entity_id', $reservation->id)->latest('id')->get(),
        ]);
    }

    public function dates(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['arrival_date' => ['required', 'date'], 'departure_date' => ['required', 'date', 'after:arrival_date']]);
        $this->frontDesk->changeDates($stay, CarbonImmutable::parse($data['arrival_date']), CarbonImmutable::parse($data['departure_date']));

        return back()->with('ok', 'Saved successfully.');
    }

    public function assign(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['room_id' => ['required', 'exists:rooms,id']]);
        $this->frontDesk->assignRoom($stay, Room::findOrFail($data['room_id']));

        return back()->with('ok', 'Saved successfully.');
    }

    public function rate(Request $request, ReservationRoom $stay)
    {
        $data = $request->validate(['nightly_rate' => ['required', 'numeric', 'min:0', 'max:99999'], 'reason' => ['required', 'string', 'max:255']]);
        $old = ['rate' => $stay->nightly_rate];
        $stay->update(['nightly_rate' => $data['nightly_rate']]);
        AuditTrail::record('stay.rate', $stay, $old, ['rate' => $data['nightly_rate'], 'reason' => $data['reason']]);

        return back()->with('ok', 'Saved successfully.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->frontDesk->cancel($reservation, $data['reason']);

        return back()->with('ok', 'Reservation cancelled.');
    }

    public function confirm(Reservation $reservation)
    {
        $reservation->rooms()->where('status', ReservationStatus::Tentative->value)->update(['status' => ReservationStatus::Confirmed->value]);
        $reservation->syncStatus();
        AuditTrail::record('reservation.confirm', $reservation);

        return back()->with('ok', 'Saved successfully.');
    }
}
