<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\ReservationStatus;
use App\Models\GuestAccount;
use App\Models\HotelSetting;
use App\Models\Outlet;
use App\Models\OutletCheck;
use App\Models\OutletItem;
use App\Models\PaymentMethod;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OutletPosting
{
    public function __construct(private Folio $folio) {}

    /**
     * @param  array<int, float>  $quantities  outlet_item_id => quantity
     */
    public function settle(User $user, Outlet $outlet, array $quantities, string $settlement, ?GuestAccount $account = null, ?PaymentMethod $method = null, ?string $customer = null): OutletCheck
    {
        $items = OutletItem::query()->where('outlet_id', $outlet->id)->where('is_active', true)
            ->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        $quantities = array_filter($quantities, fn ($q, $id) => $q > 0 && $items->has($id), ARRAY_FILTER_USE_BOTH);
        if ($quantities === []) {
            throw new HotelException('Add at least one item.');
        }

        return DB::transaction(function () use ($user, $outlet, $quantities, $items, $settlement, $account, $method, $customer) {
            $shift = $user->openShift();
            if ($settlement === 'room') {
                $account = GuestAccount::query()->with('stay')->lockForUpdate()->findOrFail($account?->id);
                if (! $account->isOpen() || ! $account->allow_posting || $account->type !== AccountType::Guest || $account->stay?->status !== ReservationStatus::CheckedIn) {
                    throw new HotelException('Posting to this room is not allowed.');
                }
            } else {
                if (! $method) {
                    throw new HotelException('Choose a payment method.');
                }
                if (! $shift) {
                    throw new HotelException('Open a cashier shift first.');
                }
                $account = $this->folio->openAccount(AccountType::NonGuest, $customer ?: $outlet->name());
            }

            $subtotal = 0.0;
            foreach ($quantities as $id => $qty) {
                $subtotal += round((float) $items[$id]->price * $qty, 3);
            }
            $code = $outlet->transactionCode;
            $taxes = $this->folio->taxes($code, $subtotal);
            $check = OutletCheck::create([
                'check_no' => Sequence::next('check'),
                'outlet_id' => $outlet->id,
                'settlement' => $settlement,
                'guest_account_id' => $account->id,
                'payment_method_id' => $method?->id,
                'customer_name' => $customer,
                'subtotal' => $subtotal,
                'service_amount' => $taxes['service'],
                'tax_amount' => $taxes['tax'],
                'total' => round($subtotal + $taxes['service'] + $taxes['tax'], 3),
                'business_date' => HotelSetting::businessDate(),
                'shift_id' => $shift?->id,
                'user_id' => $user->id,
            ]);
            foreach ($quantities as $id => $qty) {
                $check->lines()->create([
                    'outlet_item_id' => $id,
                    'name' => $items[$id]->name(),
                    'quantity' => $qty,
                    'unit_price' => $items[$id]->price,
                    'amount' => round((float) $items[$id]->price * $qty, 3),
                ]);
            }
            $this->folio->post($account, $code, $subtotal, $outlet->name().' - '.$check->check_no, 1, ['outlet_check_id' => $check->id]);

            if ($settlement !== 'room') {
                $this->folio->pay($account, $method, (float) $check->total, $shift, null, false, $customer ?: $outlet->name());
                $account->update(['status' => 'closed', 'closed_at' => now()]);
            }

            return $check;
        });
    }
}
