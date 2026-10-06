<x-layouts.print :title="$payment->amount < 0 ? __('Payment voucher') : __('Receipt voucher')" :hotel="$hotel">
<table class="table table-sm table-borderless">
    <tr><th style="width:35%">{{ __('Voucher no.') }}</th><td>{{ $payment->voucher?->voucher_no }}</td></tr>
    <tr><th>{{ __('Date') }}</th><td dir="ltr">{{ $payment->business_date->toDateString() }} {{ $payment->created_at->format('H:i') }}</td></tr>
    <tr><th>{{ $payment->amount < 0 ? __('Paid to') : __('Received from') }}</th><td>{{ $payment->voucher?->received_from }}</td></tr>
    <tr><th>{{ __('Account') }}</th><td>{{ $payment->account->account_no }} · {{ $payment->account->name }}</td></tr>
    <tr><th>{{ __('Method') }}</th><td>{{ $payment->method->name() }} {{ $payment->reference ? '#'.$payment->reference : '' }}</td></tr>
    <tr><th>{{ __('Amount') }}</th><td class="fs-4 fw-semibold"><x-money :value="abs((float) $payment->amount)" /> {{ $hotel->currency }}</td></tr>
    <tr><th>{{ __('Cashier') }}</th><td>{{ $payment->user->name }}</td></tr>
</table>
<div class="d-flex justify-content-between mt-5 small"><span>{{ __('Cashier signature') }} ________</span><span>{{ __('Guest signature') }} ________</span></div>
@if ($payment->voucher?->print_count > 1)<div class="small text-muted mt-3">{{ __('Copy') }} #{{ $payment->voucher->print_count }}</div>@endif
</x-layouts.print>
