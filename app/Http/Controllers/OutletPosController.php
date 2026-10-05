<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\ReservationStatus;
use App\Models\GuestAccount;
use App\Models\HotelSetting;
use App\Models\Outlet;
use App\Models\OutletCheck;
use App\Models\PaymentMethod;
use App\Services\OutletPosting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OutletPosController extends Controller
{
    public function index()
    {
        $outlets = Outlet::query()->where('is_active', true)->withCount(['items' => fn ($q) => $q->where('is_active', true)])->orderBy('id')->get();
        $today = HotelSetting::businessDate();
        $checks = OutletCheck::query()->with(['outlet', 'account.stay.room', 'method'])->whereDate('business_date', $today)->latest('id')->limit(50)->get();

        return view('pos.index', compact('outlets', 'checks', 'today'));
    }

    public function show(Outlet $outlet)
    {
        abort_unless($outlet->is_active, 404);

        return view('pos.show', [
            'outlet' => $outlet,
            'items' => $outlet->items()->where('is_active', true)->orderBy('category')->orderBy('name_en')->get()->groupBy(fn ($i) => $i->category ?: __('General')),
            'accounts' => GuestAccount::query()->with('stay.room')->where('type', AccountType::Guest->value)->where('status', 'open')->where('allow_posting', true)
                ->whereHas('stay', fn ($q) => $q->where('status', ReservationStatus::CheckedIn->value))->get()->sortBy(fn ($a) => $a->stay->room?->room_number),
            'methods' => PaymentMethod::query()->where('is_active', true)->orderBy('id')->get(),
            'shift' => auth()->user()->openShift(),
            'hotel' => HotelSetting::current(),
        ]);
    }

    public function store(Request $request, Outlet $outlet, OutletPosting $posting)
    {
        $data = $request->validate([
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'settlement' => ['required', 'in:room,direct'],
            'guest_account_id' => ['required_if:settlement,room', 'nullable', 'exists:guest_accounts,id'],
            'payment_method_id' => ['required_if:settlement,direct', 'nullable', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'customer_name' => ['nullable', 'string', 'max:150'],
        ]);
        $quantities = collect($data['qty'])->map(fn ($q) => (float) $q)->filter()->all();
        $check = $posting->settle(
            $request->user(), $outlet, $quantities, $data['settlement'],
            isset($data['guest_account_id']) ? GuestAccount::find($data['guest_account_id']) : null,
            isset($data['payment_method_id']) ? PaymentMethod::find($data['payment_method_id']) : null,
            $data['customer_name'] ?? null,
        );

        return redirect()->route('pos.show', $outlet)->with('ok', 'Check :no saved.')->with('ok_replace', ['no' => $check->check_no])->with('print', route('pos.check', $check));
    }

    public function check(OutletCheck $check)
    {
        $check->load(['outlet', 'lines', 'account.stay.room', 'method', 'user']);

        return view('print.check', ['check' => $check, 'hotel' => HotelSetting::current()]);
    }
}
