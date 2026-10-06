@php use App\Enums\ReservationStatus as RS; $stay = $account->stay; $inHouse = $stay?->status === RS::CheckedIn; @endphp
<x-layouts.app :title="$account->account_no">
<x-page-head :title="$account->name" :subtitle="$account->account_no.' · '.$account->type->label()" icon="wallet">
    <x-slot:badge><span class="badge text-bg-{{ $account->isOpen() ? 'success' : 'secondary' }}">{{ $account->isOpen() ? __('Open') : __('Closed') }}</span></x-slot:badge>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light" href="{{ route('accounts.folio', $account) }}" target="_blank">{{ __('Print folio') }}</a>
        @foreach ($account->invoices as $inv)<a class="btn btn-light" href="{{ route('invoices.show', $inv) }}" target="_blank">{{ __('Invoice') }} {{ $inv->invoice_no }}</a>@endforeach
    </div>
</x-page-head>
<div class="row g-3">
    <div class="{{ $account->isOpen() ? 'col-xl-8' : 'col-12' }}">
        @if ($stay)
            <div class="card card-body mb-3">
                <div class="d-flex flex-wrap align-items-center gap-4 small">
                    <span>{{ __('Room') }}: <strong>{{ $stay->room?->room_number }}</strong></span>
                    <span>{{ __('Reservation') }}: <a href="{{ route('reservations.show', $stay->reservation_id) }}">{{ $stay->reservation->reservation_no }}</a></span>
                    <span>{{ __('Arrival') }}: <span dir="ltr">{{ $stay->arrival_date->toDateString() }}</span></span>
                    <span>{{ __('Departure') }}: <span dir="ltr">{{ $stay->departure_date->toDateString() }}</span></span>
                    <span>{{ __('Rate') }}: <x-money :value="$stay->nightly_rate" /></span>
                    <x-status :status="$stay->status" />
                </div>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Code') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Net') }}</th><th class="text-end">{{ __('Service + tax') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('User') }}</th><th></th></tr></thead>
                <tbody>
                @forelse ($lines as $l)
                    <tr class="{{ $l->reversedBy ? 'folio-reversed' : '' }}">
                        <td dir="ltr" class="text-nowrap">{{ $l->business_date->toDateString() }}</td>
                        <td>{{ $l->code->code }}</td>
                        <td>{{ $l->description }} @if ((float) $l->quantity != 1)<small class="text-muted">({{ (float) $l->quantity }} × <x-money :value="$l->unit_price" />)</small>@endif
                            @if ($l->reason)<div class="small text-muted">{{ $l->reason }}</div>@endif</td>
                        <td class="text-end"><x-money :value="$l->amount" /></td>
                        <td class="text-end"><x-money :value="(float) $l->service_amount + (float) $l->tax_amount" /></td>
                        <td class="text-end"><x-money :value="$l->total()" :sign="true" /></td>
                        <td class="small">{{ $l->user->name }}</td>
                        <td class="text-end text-nowrap">
                            @if ($l->payment?->voucher)
                                <a class="btn btn-sm btn-light" href="{{ route('payments.receipt', $l->payment) }}" target="_blank">{{ $l->payment->voucher->voucher_no }}</a>
                            @elseif ($account->isOpen() && ! $l->reversedBy && ! $l->reversal_of_id && ! $l->payment_id && $l->code->code !== 'TRANS')
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reverseModal" data-action="{{ route('transactions.reverse', $l) }}" data-desc="{{ $l->description }}">{{ __('Reverse') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted text-center">{{ __('No transactions yet.') }}</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="5" class="text-end">{{ __('Balance') }}</th><th class="text-end fs-5"><x-money :value="$account->balance" :sign="true" /></th><th colspan="2"></th></tr></tfoot>
            </table>
        </div>
    </div>
    @if ($account->isOpen())
    <div class="col-xl-4">
            <div class="balance-card mb-3 d-flex justify-content-between align-items-center">
                <div><div class="label">{{ __('Balance') }}</div><div class="value" dir="ltr">{{ number_format((float) $account->balance, 3) }}</div></div>
                <div class="text-end small"><div class="label">{{ $account->balance > 0 ? __('Due from guest') : ($account->balance < 0 ? __('Credit to guest') : __('Settled')) }}</div><div>{{ \App\Models\HotelSetting::current()->currency }}</div></div>
            </div>
            @unless ($shift)
                <div class="alert alert-warning">{{ __('Open a cashier shift first.') }} <a href="{{ route('shifts.index') }}">{{ __('Open shift') }}</a></div>
            @endunless
            <form method="post" action="{{ route('accounts.pay', $account) }}" class="card mb-3">
                @csrf
                <div class="card-header">{{ __('Payment') }}</div>
                <div class="card-body">
                    <div class="btn-group w-100 mb-3" role="group">
                        @foreach (['payment' => 'Payment', 'deposit' => 'Deposit', 'refund' => 'Refund'] as $k => $l)
                            <input type="radio" class="btn-check" name="kind" value="{{ $k }}" id="kind{{ $k }}" @checked($k === ($account->balance > 0 ? 'payment' : ($account->balance < 0 ? 'refund' : 'deposit')))>
                            <label class="btn btn-outline-primary btn-sm" for="kind{{ $k }}">{{ __($l) }}</label>
                        @endforeach
                    </div>
                    <div class="mb-2"><label class="form-label">{{ __('Method') }}</label>
                        <select name="payment_method_id" class="form-select">@foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name() }}</option>@endforeach</select></div>
                    <div class="mb-2"><label class="form-label">{{ __('Amount') }}</label><input type="number" step="0.001" min="0.001" name="amount" class="form-control" value="{{ $account->balance != 0 ? abs((float) $account->balance) : '' }}" required></div>
                    <div class="mb-2"><label class="form-label">{{ __('Reference') }}</label><input name="reference" class="form-control" maxlength="60"></div>
                    <div class="mb-0"><label class="form-label">{{ __('Received from') }}</label><input name="received_from" class="form-control" value="{{ $account->name }}"></div>
                </div>
                <div class="card-footer"><button class="btn btn-primary w-100" @disabled(! $shift)>{{ __('Save and print receipt') }}</button></div>
            </form>
            <form method="post" action="{{ route('accounts.charge', $account) }}" class="card mb-3">
                @csrf
                <div class="card-header">{{ __('Post charge') }}</div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">{{ __('Transaction code') }}</label>
                        <select name="transaction_code_id" class="form-select">@foreach ($codes as $c)<option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name() }}</option>@endforeach</select></div>
                    <div class="row g-2 mb-2">
                        <div class="col-8"><label class="form-label">{{ __('Unit price') }}</label><input type="number" step="0.001" name="unit_price" class="form-control" required></div>
                        <div class="col-4"><label class="form-label">{{ __('Qty') }}</label><input type="number" step="0.01" min="0.01" name="quantity" value="1" class="form-control" required></div>
                    </div>
                    <div class="mb-2"><label class="form-label">{{ __('Description') }}</label><input name="description" class="form-control" maxlength="200"></div>
                    <div class="mb-0"><label class="form-label">{{ __('Reason') }}</label><input name="reason" class="form-control" maxlength="255" placeholder="{{ __('Required for adjustments') }}"></div>
                </div>
                <div class="card-footer"><button class="btn btn-light w-100">{{ __('Post') }}</button></div>
            </form>
            @if ($inHouse)
                <form method="post" action="{{ route('stays.check-out', $stay) }}" class="card mb-3" id="checkout">
                    @csrf
                    <div class="card-header">{{ __('Check-out') }}</div>
                    <div class="card-body">
                        @if ($stay->departure_date->gt($today))<div class="alert alert-warning small">{{ __('Early departure: the stay will end today.') }}</div>@endif
                        <label class="form-label">{{ __('Transfer the balance to company (city ledger)') }}</label>
                        <select name="company_id" class="form-select"><option value="">{{ __('No - guest pays') }}</option>
                            @foreach ($companies as $c)<option value="{{ $c->id }}" @selected($stay->reservation->company_id === $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="card-footer"><button class="btn btn-success w-100" onclick="return confirm(@json(__('Check out this guest?')))">{{ __('Check-out and issue invoice') }}</button></div>
                </form>
                <form method="post" action="{{ route('stays.move', $stay) }}" class="card mb-3">
                    @csrf
                    <div class="card-header">{{ __('Room move') }}</div>
                    <div class="card-body">
                        <select name="room_id" class="form-select mb-2" required><option value=""></option>
                            @foreach ($moveRooms as $r)<option value="{{ $r->id }}">{{ $r->room_number }} · {{ $r->roomType->name() }}</option>@endforeach
                        </select>
                        <input name="reason" class="form-control" placeholder="{{ __('Reason') }}" required maxlength="255">
                    </div>
                    <div class="card-footer"><button class="btn btn-light w-100">{{ __('Move') }}</button></div>
                </form>
            @endif
    </div>
    @endif
</div>
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog"><form method="post" class="modal-content" id="reverseForm">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('Reverse') }}: <span id="reverseDesc"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('Reason') }}</label><input name="reason" class="form-control" required maxlength="255"></div>
        <div class="modal-footer"><button class="btn btn-danger">{{ __('Reverse') }}</button></div>
    </form></div>
</div>
<x-slot:scripts>
<script>
document.getElementById('reverseModal').addEventListener('show.bs.modal', e => {
    document.getElementById('reverseForm').action = e.relatedTarget.dataset.action;
    document.getElementById('reverseDesc').textContent = e.relatedTarget.dataset.desc;
});
</script>
</x-slot:scripts>
</x-layouts.app>
