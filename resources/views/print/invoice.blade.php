<x-layouts.print :title="__('Tax invoice')" :hotel="$hotel">
@php $stay = $invoice->account->stay; @endphp
<div class="d-flex justify-content-between small mb-3">
    <div>{{ __('Bill to') }}: <strong>{{ $invoice->bill_to }}</strong>@if ($invoice->tax_number)<br>{{ __('Tax number') }}: {{ $invoice->tax_number }}@endif
        @if ($stay)<br>{{ __('Guest') }}: {{ $stay->reservation->guest->full_name }} · {{ __('Room') }} {{ $stay->room?->room_number }}@endif</div>
    <div class="text-end">{{ __('Invoice no.') }} <strong>{{ $invoice->invoice_no }}</strong><br><span dir="ltr">{{ $invoice->business_date->toDateString() }}</span>
        @if ($stay)<br><span dir="ltr">{{ $stay->arrival_date->toDateString() }} → {{ $stay->departure_date->toDateString() }}</span>@endif</div>
</div>
@include('print._lines')
<table class="table table-sm w-50 ms-auto">
    <tr><th>{{ __('Subtotal') }}</th><td class="text-end"><x-money :value="$invoice->subtotal" /></td></tr>
    <tr><th>{{ __('Service') }}</th><td class="text-end"><x-money :value="$invoice->service_amount" /></td></tr>
    <tr><th>{{ __('Tax') }}</th><td class="text-end"><x-money :value="$invoice->tax_amount" /></td></tr>
    <tr class="fs-5"><th>{{ __('Total') }}</th><td class="text-end"><x-money :value="$invoice->total" /> {{ $hotel->currency }}</td></tr>
    <tr><th>{{ __('Paid') }}</th><td class="text-end"><x-money :value="$invoice->paid" /></td></tr>
    @if (round((float) $invoice->total - (float) $invoice->paid, 3) != 0)
        <tr><th>{{ __('Transferred to city ledger') }}</th><td class="text-end"><x-money :value="(float) $invoice->total - (float) $invoice->paid" /></td></tr>
    @endif
</table>
</x-layouts.print>
