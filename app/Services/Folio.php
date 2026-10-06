<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\AuditTrail;
use App\Models\GuestAccount;
use App\Models\GuestTransaction;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ReceiptVoucher;
use App\Models\Sequence;
use App\Models\Shift;
use App\Models\TransactionCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** The only place that writes guest_transactions; lines are append-only. */
class Folio
{
    public function openAccount(AccountType $type, string $name, array $attributes = []): GuestAccount
    {
        return GuestAccount::create([
            'account_no' => Sequence::next('account'),
            'type' => $type,
            'name' => $name,
            'status' => 'open',
            'balance' => 0,
        ] + $attributes);
    }

    /** @return array{service: float, tax: float} */
    public function taxes(TransactionCode $code, float $amount): array
    {
        $settings = HotelSetting::current();
        $service = $code->has_service ? round($amount * (float) $settings->service_percent / 100, 3) : 0.0;
        $tax = $code->is_taxable ? round(($amount + $service) * (float) $settings->tax_percent / 100, 3) : 0.0;

        return ['service' => $service, 'tax' => $tax];
    }

    /** Posts a charge (or adjustment) line; tax and service are added from the code settings. */
    public function post(GuestAccount $account, TransactionCode $code, float $unitPrice, string $description, float $quantity = 1, array $extra = []): GuestTransaction
    {
        if ($code->type === TransactionType::Payment) {
            throw new HotelException('Use the payment screen for payment codes.');
        }
        if ($unitPrice == 0.0 || $quantity <= 0) {
            throw new HotelException('Amount must not be zero.');
        }
        $amount = round($unitPrice * $quantity, 3);
        $taxes = $this->taxes($code, abs($amount));
        $sign = $amount < 0 ? -1 : 1;

        return $this->write($account, [
            'transaction_code_id' => $code->id,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'service_amount' => $sign * $taxes['service'],
            'tax_amount' => $sign * $taxes['tax'],
        ] + $extra);
    }

    public function reverse(GuestTransaction $line, string $reason): GuestTransaction
    {
        return DB::transaction(function () use ($line, $reason) {
            $line = GuestTransaction::query()->lockForUpdate()->findOrFail($line->id);
            if ($line->reversal_of_id || $line->reversedBy()->exists()) {
                throw new HotelException('This line was already reversed.');
            }
            if ($line->payment_id) {
                throw new HotelException('Payments are corrected with a refund, not a reversal.');
            }
            if ($line->transferred_from_account_id || $line->code->code === TransactionCode::TRANSFER) {
                throw new HotelException('Transfers cannot be reversed.');
            }

            $reversal = $this->write($line->account, [
                'transaction_code_id' => $line->transaction_code_id,
                'description' => __('Reversal').': '.$line->description,
                'quantity' => $line->quantity,
                'unit_price' => -1 * (float) $line->unit_price,
                'amount' => -1 * (float) $line->amount,
                'service_amount' => -1 * (float) $line->service_amount,
                'tax_amount' => -1 * (float) $line->tax_amount,
                'outlet_check_id' => $line->outlet_check_id,
                'reversal_of_id' => $line->id,
                'reason' => $reason,
            ]);
            AuditTrail::record('folio.reverse', $line, null, ['reversal_id' => $reversal->id, 'reason' => $reason]);

            return $reversal;
        });
    }

    /** Positive amount = receipt, negative amount = refund. */
    public function pay(GuestAccount $account, PaymentMethod $method, float $amount, Shift $shift, ?string $reference = null, bool $isDeposit = false, ?string $receivedFrom = null): Payment
    {
        if ($amount == 0.0) {
            throw new HotelException('Amount must not be zero.');
        }
        if (! $shift->isOpen()) {
            throw new HotelException('Open a cashier shift first.');
        }
        if ($method->requires_reference && blank($reference)) {
            throw new HotelException('A reference number is required for this payment method.');
        }

        return DB::transaction(function () use ($account, $method, $amount, $shift, $reference, $isDeposit, $receivedFrom) {
            $refund = $amount < 0;
            $payment = Payment::create([
                'payment_no' => Sequence::next('payment'),
                'guest_account_id' => $account->id,
                'payment_method_id' => $method->id,
                'amount' => round($amount, 3),
                'reference' => $reference,
                'is_deposit' => $isDeposit,
                'shift_id' => $shift->id,
                'business_date' => HotelSetting::businessDate(),
                'user_id' => auth()->id() ?? $shift->user_id,
            ]);
            ReceiptVoucher::create([
                'voucher_no' => Sequence::next($refund ? 'refund' : 'receipt'),
                'payment_id' => $payment->id,
                'type' => $refund ? 'refund' : 'receipt',
                'received_from' => $receivedFrom ?: $account->name,
            ]);
            $this->write($account, [
                'transaction_code_id' => $method->transaction_code_id,
                'description' => ($refund ? __('Refund') : ($isDeposit ? __('Deposit') : __('Payment'))).' - '.$method->name().($reference ? ' #'.$reference : ''),
                'unit_price' => -1 * round($amount, 3),
                'amount' => -1 * round($amount, 3),
                'payment_id' => $payment->id,
                'shift_id' => $shift->id,
            ]);

            return $payment;
        });
    }

    /** Moves an amount from one account to another (e.g. checkout to city ledger). */
    public function transfer(GuestAccount $from, GuestAccount $to, float $amount, string $reason): void
    {
        DB::transaction(function () use ($from, $to, $amount, $reason) {
            $code = TransactionCode::byCode(TransactionCode::TRANSFER);
            $this->write($from, [
                'transaction_code_id' => $code->id,
                'description' => __('Transfer to').' '.$to->account_no.' - '.$to->name,
                'unit_price' => -$amount,
                'amount' => -$amount,
                'reason' => $reason,
            ]);
            $this->write($to, [
                'transaction_code_id' => $code->id,
                'description' => __('Transfer from').' '.$from->account_no.' - '.$from->name,
                'unit_price' => $amount,
                'amount' => $amount,
                'transferred_from_account_id' => $from->id,
                'reason' => $reason,
            ], allowClosed: false);
        });
    }

    private function write(GuestAccount $account, array $data, bool $allowClosed = false): GuestTransaction
    {
        return DB::transaction(function () use ($account, $data, $allowClosed) {
            $account = GuestAccount::query()->lockForUpdate()->findOrFail($account->id);
            if (! $allowClosed && ! $account->isOpen()) {
                throw new HotelException('Account :no is closed.', ['no' => $account->account_no]);
            }
            $user = auth()->user();
            $line = GuestTransaction::create($data + [
                'guest_account_id' => $account->id,
                'business_date' => HotelSetting::businessDate(),
                'quantity' => 1,
                'user_id' => $user instanceof User ? $user->id : $data['user_id'] ?? null,
                'shift_id' => $user instanceof User ? $user->openShift()?->id : null,
            ]);
            $account->update(['balance' => round((float) $account->balance + $line->total(), 3)]);

            return $line;
        });
    }
}
