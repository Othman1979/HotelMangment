<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ReservationStatus;
use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\GuestAccount;
use App\Models\HotelSetting;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Sequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FrontDesk
{
    public function __construct(private Availability $availability, private Folio $folio) {}

    /**
     * @param  array<string, mixed>  $data  reservation header
     * @param  list<array{room_type_id: int, room_id?: int|null, adults: int, children?: int, nightly_rate?: float|null}>  $lines
     */
    public function book(array $data, array $lines): Reservation
    {
        $from = CarbonImmutable::parse($data['arrival_date']);
        $to = CarbonImmutable::parse($data['departure_date']);
        $this->assertDates($from, $to);

        return DB::transaction(function () use ($data, $lines, $from, $to) {
            Room::query()->lockForUpdate()->get(['id']);
            $reservation = Reservation::create($data + [
                'reservation_no' => Sequence::next('reservation'),
                'created_by' => auth()->id(),
            ]);
            $wanted = [];
            foreach ($lines as $line) {
                $type = RoomType::findOrFail($line['room_type_id']);
                $wanted[$type->id] = ($wanted[$type->id] ?? 0) + 1;
                if ($this->availability->availableCount($type, $from, $to) < 1) {
                    throw new HotelException('No :type rooms available for these dates.', ['type' => $type->name()]);
                }
                $roomId = $line['room_id'] ?? null;
                if ($roomId) {
                    $room = Room::findOrFail($roomId);
                    if ($room->room_type_id !== $type->id) {
                        throw new HotelException('Room :room is not of the selected type.', ['room' => $room->room_number]);
                    }
                    if (! $this->availability->isRoomFree($room, $from, $to)) {
                        throw new HotelException('Room :room is not free for these dates.', ['room' => $room->room_number]);
                    }
                }
                $reservation->rooms()->create([
                    'room_type_id' => $type->id,
                    'room_id' => $roomId,
                    'arrival_date' => $from,
                    'departure_date' => $to,
                    'adults' => $line['adults'] ?? 1,
                    'children' => $line['children'] ?? 0,
                    'nightly_rate' => $line['nightly_rate'] ?? $type->base_rate,
                    'status' => $reservation->status,
                ]);
            }
            AuditTrail::record('reservation.create', $reservation, null, ['rooms' => count($lines)]);

            return $reservation;
        });
    }

    public function changeDates(ReservationRoom $stay, CarbonImmutable $from, CarbonImmutable $to): void
    {
        DB::transaction(function () use ($stay, $from, $to) {
            Room::query()->lockForUpdate()->get(['id']);
            $stay->refresh();
            if ($stay->status === ReservationStatus::CheckedIn) {
                $from = $stay->arrival_date;
                if ($to->lte(HotelSetting::businessDate())) {
                    throw new HotelException('Departure must be after the business date.');
                }
            } elseif (! in_array($stay->status->value, ReservationStatus::active(), true)) {
                throw new HotelException('This stay can no longer be changed.');
            } else {
                $this->assertDates($from, $to);
            }
            if ($stay->room_id) {
                if (! $this->availability->isRoomFree($stay->room, $from, $to, $stay->id)) {
                    throw new HotelException('Room :room is not free for these dates.', ['room' => $stay->room->room_number]);
                }
            } elseif ($this->availability->availableCount($stay->roomType, $from, $to, $stay->id) < 1) {
                throw new HotelException('No :type rooms available for these dates.', ['type' => $stay->roomType->name()]);
            }
            $old = ['arrival' => $stay->arrival_date->toDateString(), 'departure' => $stay->departure_date->toDateString()];
            $stay->update(['arrival_date' => $from, 'departure_date' => $to]);
            $this->syncHeaderDates($stay->reservation);
            AuditTrail::record('stay.dates', $stay, $old, ['arrival' => $from->toDateString(), 'departure' => $to->toDateString()]);
        });
    }

    public function assignRoom(ReservationRoom $stay, Room $room): void
    {
        DB::transaction(function () use ($stay, $room) {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            if ($room->room_type_id !== $stay->room_type_id) {
                throw new HotelException('Room :room is not of the selected type.', ['room' => $room->room_number]);
            }
            if (! $this->availability->isRoomFree($room, $stay->arrival_date, $stay->departure_date, $stay->id)) {
                throw new HotelException('Room :room is not free for these dates.', ['room' => $room->room_number]);
            }
            $stay->update(['room_id' => $room->id]);
        });
    }

    public function ensureAccount(ReservationRoom $stay): GuestAccount
    {
        return $stay->account ?? $this->folio->openAccount(AccountType::Guest, $stay->reservation->guest->full_name, [
            'reservation_room_id' => $stay->id,
            'guest_id' => $stay->reservation->guest_id,
            'company_id' => $stay->reservation->company_id,
        ]);
    }

    public function checkIn(ReservationRoom $stay, Room $room): GuestAccount
    {
        return DB::transaction(function () use ($stay, $room) {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $stay = ReservationRoom::query()->lockForUpdate()->findOrFail($stay->id);
            $today = HotelSetting::businessDate();
            if (! in_array($stay->status, [ReservationStatus::Confirmed, ReservationStatus::Tentative], true)) {
                throw new HotelException('Only confirmed reservations can be checked in.');
            }
            if (! $stay->arrival_date->isSameDay($today)) {
                throw new HotelException('Arrival date must be the business date (:date). Change the dates first.', ['date' => $today->toDateString()]);
            }
            if ($stay->reservation->guest->is_blacklisted) {
                throw new HotelException('This guest is blacklisted.');
            }
            if ($room->room_type_id !== $stay->room_type_id) {
                throw new HotelException('Room :room is not of the selected type.', ['room' => $room->room_number]);
            }
            if (! $room->isReadyForCheckIn()) {
                throw new HotelException('Room :room is not ready (status :status).', ['room' => $room->room_number, 'status' => $room->statusCode()]);
            }
            if (! $this->availability->isRoomFree($room, $stay->arrival_date, $stay->departure_date, $stay->id)) {
                throw new HotelException('Room :room is not free for these dates.', ['room' => $room->room_number]);
            }

            $stay->update(['room_id' => $room->id, 'status' => ReservationStatus::CheckedIn, 'checked_in_at' => now()]);
            $room->update(['occupancy_status' => OccupancyStatus::Occupied]);
            $stay->reservation->syncStatus();
            $account = $this->ensureAccount($stay->fresh());
            AuditTrail::record('stay.check_in', $stay, null, ['room' => $room->room_number]);

            return $account;
        });
    }

    public function moveRoom(ReservationRoom $stay, Room $to, string $reason): void
    {
        DB::transaction(function () use ($stay, $to, $reason) {
            $to = Room::query()->lockForUpdate()->findOrFail($to->id);
            if ($stay->status !== ReservationStatus::CheckedIn) {
                throw new HotelException('Only in-house guests can change rooms.');
            }
            if (! $to->isReadyForCheckIn() || ! $this->availability->isRoomFree($to, HotelSetting::businessDate(), $stay->departure_date, $stay->id)) {
                throw new HotelException('Room :room is not ready (status :status).', ['room' => $to->room_number, 'status' => $to->statusCode()]);
            }
            $from = $stay->room;
            $from->update(['occupancy_status' => OccupancyStatus::Vacant, 'housekeeping_status' => HousekeepingStatus::Dirty]);
            $to->update(['occupancy_status' => OccupancyStatus::Occupied]);
            $stay->update(['room_id' => $to->id, 'room_type_id' => $to->room_type_id]);
            AuditTrail::record('stay.move', $stay, ['room' => $from->room_number], ['room' => $to->room_number, 'reason' => $reason]);
        });
    }

    public function checkOut(ReservationRoom $stay, ?Company $cityLedger = null): Invoice
    {
        return DB::transaction(function () use ($stay, $cityLedger) {
            $stay = ReservationRoom::query()->lockForUpdate()->findOrFail($stay->id);
            if ($stay->status !== ReservationStatus::CheckedIn) {
                throw new HotelException('Only in-house guests can check out.');
            }
            $account = $this->ensureAccount($stay);
            $balance = round((float) $account->fresh()->balance, 3);
            if ($balance != 0.0) {
                if (! $cityLedger || $balance < 0) {
                    throw new HotelException('Settle the balance (:amount) before check-out.', ['amount' => number_format($balance, 3)]);
                }
                $ledger = $this->cityLedgerFor($cityLedger);
                $this->folio->transfer($account, $ledger, $balance, 'check-out');
            }
            $invoice = $this->invoice($account->fresh());
            $account->update(['status' => 'closed', 'closed_at' => now()]);

            $today = HotelSetting::businessDate();
            $stay->update([
                'status' => ReservationStatus::CheckedOut,
                'checked_out_at' => now(),
                'departure_date' => $stay->departure_date->gt($today) ? $today->max($stay->arrival_date->addDay()) : $stay->departure_date,
            ]);
            $stay->room->update(['occupancy_status' => OccupancyStatus::Vacant, 'housekeeping_status' => HousekeepingStatus::Dirty]);
            $stay->reservation->syncStatus();
            AuditTrail::record('stay.check_out', $stay, null, ['invoice' => $invoice->invoice_no]);

            return $invoice;
        });
    }

    public function cancel(Reservation $reservation, string $reason): void
    {
        DB::transaction(function () use ($reservation, $reason) {
            if ($reservation->rooms()->where('status', ReservationStatus::CheckedIn->value)->exists()) {
                throw new HotelException('Guests of this reservation are in-house.');
            }
            $reservation->rooms()->whereIn('status', ReservationStatus::active())->update(['status' => ReservationStatus::Cancelled->value]);
            $reservation->update(['status' => ReservationStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            AuditTrail::record('reservation.cancel', $reservation, null, ['reason' => $reason]);
        });
    }

    public function cityLedgerFor(Company $company): GuestAccount
    {
        return GuestAccount::query()->where('type', AccountType::CityLedger->value)->where('company_id', $company->id)->where('status', 'open')->first()
            ?? $this->folio->openAccount(AccountType::CityLedger, $company->name, ['company_id' => $company->id]);
    }

    public function invoice(GuestAccount $account): Invoice
    {
        $charges = $account->transactions()->whereNull('payment_id')->whereNull('transferred_from_account_id')
            ->whereHas('code', fn ($q) => $q->where('code', '!=', 'TRANS'))->get();
        $paid = -1 * (float) $account->transactions()->whereNotNull('payment_id')->sum('amount');
        $company = $account->company;

        return Invoice::create([
            'invoice_no' => Sequence::next('invoice'),
            'guest_account_id' => $account->id,
            'bill_to' => $company?->name ?? $account->name,
            'tax_number' => $company?->tax_number,
            'subtotal' => $charges->sum('amount'),
            'service_amount' => $charges->sum('service_amount'),
            'tax_amount' => $charges->sum('tax_amount'),
            'total' => $charges->sum(fn ($l) => $l->total()),
            'paid' => $paid,
            'business_date' => HotelSetting::businessDate(),
            'user_id' => auth()->id(),
        ]);
    }

    private function assertDates(CarbonImmutable $from, CarbonImmutable $to): void
    {
        if ($from->lt(HotelSetting::businessDate())) {
            throw new HotelException('Arrival cannot be before the business date.');
        }
        if ($to->lte($from)) {
            throw new HotelException('Departure must be after arrival.');
        }
        if ($from->diffInDays($to) > 365) {
            throw new HotelException('A stay cannot exceed 365 nights.');
        }
    }

    private function syncHeaderDates(Reservation $reservation): void
    {
        $rooms = $reservation->rooms()->whereNotIn('status', [ReservationStatus::Cancelled->value])->get();
        if ($rooms->isNotEmpty()) {
            $reservation->update(['arrival_date' => $rooms->min('arrival_date'), 'departure_date' => $rooms->max('departure_date')]);
        }
    }
}
