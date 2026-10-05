<?php

namespace Tests\Feature;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\ReportController;
use App\Models\Company;
use App\Models\GuestAccount;
use App\Models\HotelSetting;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\OutletCheck;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TransactionCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    private function today(): string
    {
        return HotelSetting::businessDate()->toDateString();
    }

    private function book(Room $room, int $nights = 2, ?string $from = null): Reservation
    {
        $from ??= $this->today();
        $this->actingAs($this->admin)->post(route('reservations.store'), [
            'guest' => ['full_name' => 'Test Guest '.uniqid(), 'phone' => '0790000000'],
            'source' => 'phone', 'status' => 'confirmed',
            'arrival_date' => $from, 'departure_date' => date('Y-m-d', strtotime($from." +{$nights} days")),
            'rooms' => [['room_type_id' => $room->room_type_id, 'room_id' => $room->id, 'adults' => 1]],
        ])->assertSessionHasNoErrors()->assertSessionMissing('err');

        return Reservation::latest('id')->first();
    }

    private function checkIn(Reservation $reservation, Room $room): GuestAccount
    {
        $stay = $reservation->rooms()->first();
        $this->actingAs($this->admin)->post(route('stays.check-in', $stay), ['room_id' => $room->id])->assertRedirect();

        return $stay->fresh()->account;
    }

    private function openShift(): void
    {
        $this->actingAs($this->admin)->post(route('shifts.open'), ['opening_balance' => 50])->assertSessionMissing('err');
    }

    public function test_login_with_username_and_screens_render(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->post(route('login'), ['username' => 'admin', 'password' => 'wrong'])->assertSessionHas('err');
        $this->post(route('login'), ['username' => 'admin', 'password' => 'Admin@123'])->assertRedirect(route('home'));

        foreach (['home', 'rack', 'reservations.index', 'reservations.create', 'guests.index', 'accounts.index', 'shifts.index', 'pos.index', 'housekeeping.index', 'night-audit.index', 'reports.index', 'settings.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (['arrivals', 'in-house', 'departures'] as $list) {
            $this->get(route('front.index', $list))->assertOk();
        }
        foreach (['room-types', 'rooms', 'transaction-codes', 'payment-methods', 'outlets', 'outlet-items', 'companies', 'users'] as $r) {
            $this->get(route('setup.index', $r))->assertOk();
            $this->get(route('setup.create', $r))->assertOk();
        }
        $this->get(route('pos.show', Outlet::first()))->assertOk();
        foreach (ReportController::REPORTS as $r) {
            $this->get(route('reports.show', $r))->assertOk();
        }
    }

    public function test_roles_limit_access(): void
    {
        $hk = User::where('username', 'hk')->first();
        $this->actingAs($hk)->get(route('housekeeping.index'))->assertOk();
        $this->actingAs($hk)->get(route('reservations.index'))->assertForbidden();
        $this->actingAs(User::where('username', 'outlet')->first())->get(route('settings.edit'))->assertForbidden();
    }

    public function test_overlapping_booking_for_same_room_is_rejected(): void
    {
        $room = Room::where('room_number', '101')->first();
        $this->book($room, 3);
        $before = Reservation::count();

        $this->actingAs($this->admin)->post(route('reservations.store'), [
            'guest' => ['full_name' => 'Second'], 'source' => 'phone', 'status' => 'confirmed',
            'arrival_date' => date('Y-m-d', strtotime($this->today().' +2 days')),
            'departure_date' => date('Y-m-d', strtotime($this->today().' +5 days')),
            'rooms' => [['room_type_id' => $room->room_type_id, 'room_id' => $room->id, 'adults' => 1]],
        ])->assertSessionHas('err');

        $this->assertSame($before, Reservation::count());
        // Back-to-back stays are allowed.
        $this->book($room, 1, date('Y-m-d', strtotime($this->today().' +3 days')));
    }

    public function test_room_type_cannot_be_oversold(): void
    {
        $suite = RoomType::where('code', 'STE')->first();
        $data = fn () => [
            'guest' => ['full_name' => 'G'], 'source' => 'phone', 'status' => 'confirmed',
            'arrival_date' => $this->today(), 'departure_date' => date('Y-m-d', strtotime($this->today().' +1 day')),
            'rooms' => [['room_type_id' => $suite->id, 'adults' => 2]],
        ];
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->admin)->post(route('reservations.store'), $data())->assertSessionMissing('err');
        }
        $this->actingAs($this->admin)->post(route('reservations.store'), $data())->assertSessionHas('err');
    }

    public function test_full_stay_cycle_with_outlet_payment_audit_and_checkout(): void
    {
        $room = Room::where('room_number', '102')->first();
        $reservation = $this->book($room, 1);
        $account = $this->checkIn($reservation, $room);

        $this->assertSame(OccupancyStatus::Occupied, $room->fresh()->occupancy_status);
        $this->assertSame(ReservationStatus::CheckedIn, $reservation->fresh()->status);

        // Restaurant check charged to the room: 10.000 + 10% service + 16% tax = 12.760
        $outlet = Outlet::where('code', 'REST')->first();
        $item = $outlet->items()->where('name_en', 'Mixed grill')->first();
        $this->post(route('pos.store', $outlet), ['qty' => [$item->id => 1], 'settlement' => 'room', 'guest_account_id' => $account->id])->assertSessionMissing('err');
        $this->assertEquals(12.76, (float) $account->fresh()->balance);

        // Payment needs a shift.
        $cash = PaymentMethod::where('code', 'CASH')->first();
        $this->post(route('accounts.pay', $account), ['payment_method_id' => $cash->id, 'amount' => 5, 'kind' => 'deposit'])->assertSessionHas('err');
        $this->openShift();
        $this->post(route('accounts.pay', $account), ['payment_method_id' => $cash->id, 'amount' => 5, 'kind' => 'deposit'])->assertSessionMissing('err');
        $payment = Payment::latest('id')->first();
        $this->assertNotNull($payment->voucher);
        $this->get(route('payments.receipt', $payment))->assertOk()->assertSee($payment->voucher->voucher_no);
        $this->assertEquals(7.76, (float) $account->fresh()->balance);

        // Night audit is blocked by the open shift, then posts the room charge (45 + 16% = 52.200).
        $auditor = User::where('username', 'audit')->first();
        $this->actingAs($auditor)->post(route('night-audit.run'), ['confirm' => 1])->assertSessionHas('err');
        $shift = $this->admin->openShift();
        $this->actingAs($this->admin)->post(route('shifts.close', $shift), ['counted_cash' => 55])->assertSessionMissing('err');
        $this->assertEquals(55, (float) $shift->fresh()->expected_cash);
        $businessDate = $this->today();
        $this->actingAs($auditor)->post(route('night-audit.run'), ['confirm' => 1])->assertSessionMissing('err');
        $this->assertNotSame($businessDate, $this->today());
        $this->assertEquals(59.96, (float) $account->fresh()->balance);
        $this->assertSame(HousekeepingStatus::Dirty, $room->fresh()->housekeeping_status);

        // Check-out is refused while a balance is open.
        $stay = $reservation->rooms()->first();
        $this->actingAs($this->admin)->post(route('stays.check-out', $stay))->assertSessionHas('err');
        $this->openShift();
        $this->post(route('accounts.pay', $account), ['payment_method_id' => $cash->id, 'amount' => 59.96, 'kind' => 'payment'])->assertSessionMissing('err');
        $this->post(route('stays.check-out', $stay))->assertSessionMissing('err');

        $this->assertSame(ReservationStatus::CheckedOut, $stay->fresh()->status);
        $this->assertSame('closed', $account->fresh()->status);
        $this->assertSame(OccupancyStatus::Vacant, $room->fresh()->occupancy_status);
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals(64.96, (float) $invoice->total);
        $this->assertEquals(64.96, (float) $invoice->paid);
        $this->get(route('invoices.show', $invoice))->assertOk();
    }

    public function test_outlet_cash_sale_is_paid_and_closed_immediately(): void
    {
        $this->openShift();
        $outlet = Outlet::where('code', 'CAFE')->first();
        $item = $outlet->items()->first();
        $card = PaymentMethod::where('code', 'CASH')->first();
        $this->post(route('pos.store', $outlet), ['qty' => [$item->id => 2], 'settlement' => 'direct', 'payment_method_id' => $card->id])->assertSessionMissing('err');

        $check = OutletCheck::latest('id')->first();
        $this->assertSame('direct', $check->settlement);
        $this->assertSame('closed', $check->account->status);
        $this->assertEquals(0, (float) $check->account->balance);
        $this->assertEquals((float) $check->total, (float) Payment::latest('id')->first()->amount);
        $this->get(route('pos.check', $check))->assertOk();
    }

    public function test_reversal_creates_opposite_line_and_cannot_repeat(): void
    {
        $room = Room::where('room_number', '103')->first();
        $account = $this->checkIn($this->book($room, 1), $room);
        $misc = TransactionCode::where('code', 'MISC')->first();
        $this->post(route('accounts.charge', $account), ['transaction_code_id' => $misc->id, 'unit_price' => 10, 'quantity' => 1])->assertSessionMissing('err');
        $line = $account->transactions()->first();
        $this->assertEquals(11.6, (float) $account->fresh()->balance);

        $this->post(route('transactions.reverse', $line), ['reason' => 'wrong room'])->assertSessionMissing('err');
        $this->assertEquals(0, (float) $account->fresh()->balance);
        $this->assertSame(2, $account->transactions()->count());
        $this->post(route('transactions.reverse', $line), ['reason' => 'again'])->assertSessionHas('err');
    }

    public function test_dirty_room_cannot_be_checked_in_and_checkout_to_city_ledger(): void
    {
        $room = Room::where('room_number', '104')->first();
        $room->update(['housekeeping_status' => HousekeepingStatus::Dirty]);
        $reservation = $this->book($room, 1);
        $stay = $reservation->rooms()->first();
        $this->post(route('stays.check-in', $stay), ['room_id' => $room->id])->assertSessionHas('err');

        $this->post(route('housekeeping.status', $room), ['status' => 'inspected'])->assertSessionMissing('err');
        $account = $this->checkIn($reservation, $room);
        $misc = TransactionCode::where('code', 'MISC')->first();
        $this->post(route('accounts.charge', $account), ['transaction_code_id' => $misc->id, 'unit_price' => 100, 'quantity' => 1]);

        $company = Company::first();
        $this->post(route('stays.check-out', $stay), ['company_id' => $company->id])->assertSessionMissing('err');
        $ledger = GuestAccount::where('type', 'city_ledger')->where('company_id', $company->id)->first();
        $this->assertEquals(116, (float) $ledger->balance);
        $this->assertEquals(0, (float) $account->fresh()->balance);
    }

    public function test_no_show_is_marked_by_night_audit(): void
    {
        $room = Room::where('room_number', '105')->first();
        $reservation = $this->book($room, 2);
        $this->actingAs(User::where('username', 'audit')->first())->post(route('night-audit.run'), ['confirm' => 1])->assertSessionMissing('err');
        $this->assertSame(ReservationStatus::NoShow, $reservation->fresh()->status);
        $this->assertSame(ReservationStatus::NoShow, ReservationRoom::where('reservation_id', $reservation->id)->first()->status);
    }

    public function test_room_block_removes_room_from_availability(): void
    {
        $room = Room::where('room_number', '106')->first();
        $this->actingAs($this->admin)->post(route('housekeeping.block', $room), [
            'type' => 'out_of_order', 'from_date' => $this->today(), 'to_date' => $this->today(), 'reason' => 'AC broken',
        ])->assertSessionMissing('err');
        $this->assertSame('OOO', $room->fresh()->statusCode());

        $this->post(route('reservations.store'), [
            'guest' => ['full_name' => 'X'], 'source' => 'phone', 'status' => 'confirmed',
            'arrival_date' => $this->today(), 'departure_date' => date('Y-m-d', strtotime($this->today().' +1 day')),
            'rooms' => [['room_type_id' => $room->room_type_id, 'room_id' => $room->id, 'adults' => 1]],
        ])->assertSessionHas('err');
    }
}
