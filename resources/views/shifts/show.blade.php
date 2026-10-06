<x-layouts.app :title="$shift->shift_no">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Shift') }} {{ $shift->shift_no }} <small class="text-muted fs-6">{{ $shift->user->name }} · <span dir="ltr">{{ $shift->business_date->toDateString() }}</span></small></h2>
    <button class="btn btn-light" onclick="window.print()">{{ __('Print') }}</button>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>{{ __('Voucher') }}</th><th>{{ __('Account') }}</th><th>{{ __('Method') }}</th><th>{{ __('Reference') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr><td><a href="{{ route('payments.receipt', $p) }}" target="_blank">{{ $p->voucher?->voucher_no }}</a></td><td>{{ $p->account->account_no }} · {{ $p->account->name }}</td><td>{{ $p->method->name() }}</td><td>{{ $p->reference }}</td><td class="text-end"><x-money :value="$p->amount" /></td></tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">{{ __('No payments in this shift.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">{{ __('Totals by method') }}</div>
            <ul class="list-group list-group-flush">
                @foreach ($byMethod as $name => $sum)<li class="list-group-item d-flex justify-content-between"><span>{{ $name }}</span><x-money :value="$sum" /></li>@endforeach
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Opening balance') }}</span><x-money :value="$shift->opening_balance" /></li>
                <li class="list-group-item d-flex justify-content-between fw-semibold"><span>{{ __('Expected cash') }}</span><x-money :value="$shift->expected_cash ?? ((float) $shift->opening_balance + (float) $shift->cashCollected())" /></li>
                @if (! $shift->isOpen())
                    <li class="list-group-item d-flex justify-content-between"><span>{{ __('Counted cash') }}</span><x-money :value="$shift->counted_cash" /></li>
                    <li class="list-group-item d-flex justify-content-between"><span>{{ __('Difference') }}</span><x-money :value="$shift->difference" /></li>
                @endif
            </ul>
        </div>
        @if ($shift->isOpen())
            <form method="post" action="{{ route('shifts.close', $shift) }}" class="card">
                @csrf
                <div class="card-header">{{ __('Close shift') }}</div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">{{ __('Counted cash') }}</label><input type="number" step="0.001" min="0" name="counted_cash" class="form-control" required></div>
                    <div><label class="form-label">{{ __('Notes') }}</label><input name="notes" class="form-control" maxlength="255"></div>
                </div>
                <div class="card-footer"><button class="btn btn-primary w-100">{{ __('Close shift') }}</button></div>
            </form>
        @endif
    </div>
</div>
</x-layouts.app>
