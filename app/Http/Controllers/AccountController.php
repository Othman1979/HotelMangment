<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\GuestAccount;
use App\Models\GuestTransaction;
use App\Models\HotelSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\TransactionCode;
use App\Services\Availability;
use App\Services\Folio;
use App\Services\HotelException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(private Folio $folio) {}

    public function index(Request $request)
    {
        $type = $request->input('type');
        $q = trim((string) $request->input('q'));
        $accounts = GuestAccount::query()->with(['stay.room'])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->where('status', $request->input('status', 'open'))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('account_no', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->latest('id')->paginate(30)->withQueryString();

        return view('accounts.index', compact('accounts', 'type', 'q'));
    }

    public function show(GuestAccount $account, Availability $availability)
    {
        $account->load(['stay.room', 'stay.reservation.guest', 'stay.reservation.company', 'company', 'invoices']);
        $lines = $account->transactions()->with(['code', 'user', 'reversedBy', 'payment.voucher'])->orderBy('id')->get();
        $stay = $account->stay;
        $moveRooms = $stay?->status === ReservationStatus::CheckedIn
            ? $availability->freeRooms(null, HotelSetting::businessDate(), $stay->departure_date, $stay->id)->filter->isReadyForCheckIn()
            : collect();

        return view('accounts.show', [
            'account' => $account,
            'lines' => $lines,
            'codes' => TransactionCode::query()->where('is_active', true)->where('is_manual', true)->where('type', '!=', TransactionType::Payment->value)->orderBy('code')->get(),
            'methods' => PaymentMethod::query()->where('is_active', true)->orderBy('id')->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(),
            'shift' => auth()->user()->openShift(),
            'moveRooms' => $moveRooms,
            'today' => HotelSetting::businessDate(),
        ]);
    }

    public function charge(Request $request, GuestAccount $account)
    {
        $data = $request->validate([
            'transaction_code_id' => ['required', Rule::exists('transaction_codes', 'id')->where('is_manual', true)->where('is_active', true)],
            'unit_price' => ['required', 'numeric', 'not_in:0', 'min:-99999', 'max:99999'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'description' => ['nullable', 'string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $code = TransactionCode::findOrFail($data['transaction_code_id']);
        if ((float) $data['unit_price'] < 0 && $code->type !== TransactionType::Adjustment) {
            throw new HotelException('Negative amounts are only allowed for adjustment codes.');
        }
        $this->folio->post($account, $code, (float) $data['unit_price'], ($data['description'] ?? null) ?: $code->name(), (float) $data['quantity'], ['reason' => $data['reason'] ?? null]);

        return back()->with('ok', 'Posted successfully.');
    }

    public function pay(Request $request, GuestAccount $account)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'kind' => ['required', 'in:payment,deposit,refund'],
            'reference' => ['nullable', 'string', 'max:60'],
            'received_from' => ['nullable', 'string', 'max:150'],
        ]);
        $shift = auth()->user()->openShift() ?? throw new HotelException('Open a cashier shift first.');
        $amount = $data['kind'] === 'refund' ? -1 * (float) $data['amount'] : (float) $data['amount'];
        $payment = $this->folio->pay($account, PaymentMethod::findOrFail($data['payment_method_id']), $amount, $shift, $data['reference'] ?? null, $data['kind'] === 'deposit', $data['received_from'] ?? null);

        return redirect()->route('accounts.show', $account)->with('ok', 'Payment recorded.')->with('print', route('payments.receipt', $payment));
    }

    public function reverse(Request $request, GuestTransaction $line)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->folio->reverse($line, $data['reason']);

        return back()->with('ok', 'Line reversed.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['account', 'method', 'user', 'voucher']);
        $payment->voucher?->increment('print_count');

        return view('print.receipt', ['payment' => $payment, 'hotel' => HotelSetting::current()]);
    }

    public function invoice(Invoice $invoice)
    {
        $invoice->load(['account.stay.room', 'account.stay.reservation.guest', 'user']);
        $lines = $invoice->account->transactions()->with('code')->orderBy('id')->get();

        return view('print.invoice', ['invoice' => $invoice, 'lines' => $lines, 'hotel' => HotelSetting::current()]);
    }

    public function folio(GuestAccount $account)
    {
        $account->load(['stay.room', 'stay.reservation.guest']);
        $lines = $account->transactions()->with('code')->orderBy('id')->get();

        return view('print.folio', ['account' => $account, 'lines' => $lines, 'hotel' => HotelSetting::current()]);
    }
}
