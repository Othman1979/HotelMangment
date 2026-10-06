<?php

namespace App\Services;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ReservationStatus;
use App\Enums\ServiceStatus;
use App\Enums\TransactionType;
use App\Models\AuditTrail;
use App\Models\GuestTransaction;
use App\Models\HotelSetting;
use App\Models\NightAudit;
use App\Models\Payment;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\Shift;
use App\Models\TransactionCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NightAuditor
{
    public function __construct(private Folio $folio) {}

    /** @return array{open_shifts: Collection, due_out: Collection, arrivals: Collection, in_house: Collection} */
    public function checks(CarbonImmutable $date): array
    {
        return [
            'open_shifts' => Shift::query()->with('user')->where('status', 'open')->where('business_date', '<=', $date->toDateString())->get(),
            'due_out' => ReservationRoom::query()->with(['reservation.guest', 'room'])
                ->where('status', ReservationStatus::CheckedIn->value)->where('departure_date', '<=', $date->toDateString())->get(),
            'arrivals' => ReservationRoom::query()->with(['reservation.guest', 'roomType'])
                ->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::Tentative->value])
                ->where('arrival_date', '<=', $date->toDateString())->get(),
            'in_house' => ReservationRoom::query()->with(['reservation.guest', 'room'])
                ->where('status', ReservationStatus::CheckedIn->value)->get(),
        ];
    }

    public function run(User $user): NightAudit
    {
        return DB::transaction(function () use ($user) {
            $settings = HotelSetting::query()->lockForUpdate()->firstOrFail();
            $date = $settings->business_date;
            $checks = $this->checks($date);
            if ($checks['open_shifts']->isNotEmpty()) {
                throw new HotelException('Close all cashier shifts before the night audit.');
            }
            if ($checks['due_out']->isNotEmpty()) {
                throw new HotelException('Check out or extend the guests due out today before the night audit.');
            }
            if (NightAudit::query()->where('business_date', $date->toDateString())->exists()) {
                throw new HotelException('The night audit for :date was already run.', ['date' => $date->toDateString()]);
            }

            $audit = NightAudit::create(['business_date' => $date, 'user_id' => $user->id, 'started_at' => now()]);
            $log = [];

            foreach ($checks['arrivals'] as $stay) {
                $stay->update(['status' => ReservationStatus::NoShow]);
                $stay->reservation->syncStatus();
                $log[] = ['step' => 'no_show', 'reservation' => $stay->reservation->reservation_no];
            }

            $roomCode = TransactionCode::byCode(TransactionCode::ROOM);
            foreach ($checks['in_house'] as $stay) {
                $account = app(FrontDesk::class)->ensureAccount($stay);
                $this->folio->post($account, $roomCode, (float) $stay->nightly_rate,
                    __('Room charge').' '.$stay->room->room_number.' - '.$date->toDateString(), 1,
                    ['night_audit_id' => $audit->id, 'user_id' => $user->id]);
                $log[] = ['step' => 'room_charge', 'room' => $stay->room->room_number, 'amount' => (float) $stay->nightly_rate];
            }

            Room::query()->where('occupancy_status', OccupancyStatus::Occupied->value)
                ->update(['housekeeping_status' => HousekeepingStatus::Dirty->value]);

            $lines = GuestTransaction::query()->where('business_date', $date->toDateString())->with('code')->get();
            $revenue = $lines->filter(fn ($l) => $l->code->type !== TransactionType::Payment && $l->code->code !== TransactionCode::TRANSFER);
            $audit->update([
                'finished_at' => now(),
                'rooms_total' => Room::query()->where('is_active', true)->count(),
                'rooms_occupied' => $checks['in_house']->count(),
                'rooms_out_of_order' => Room::query()->where('is_active', true)->where('service_status', '!=', ServiceStatus::InService->value)->count(),
                'room_revenue' => $revenue->filter(fn ($l) => $l->code->revenue_group === 'rooms')->sum('amount'),
                'other_revenue' => $revenue->filter(fn ($l) => $l->code->revenue_group !== 'rooms')->sum('amount'),
                'tax_total' => $revenue->sum(fn ($l) => (float) $l->tax_amount + (float) $l->service_amount),
                'payments_total' => Payment::query()->where('business_date', $date->toDateString())->sum('amount'),
                'no_shows' => $checks['arrivals']->count(),
                'log' => $log,
            ]);

            $settings->update(['business_date' => $date->addDay()]);
            AuditTrail::record('night_audit.run', $audit, ['business_date' => $date->toDateString()], ['business_date' => $date->addDay()->toDateString()]);

            return $audit;
        });
    }
}
