<x-layouts.print :title="__('Check').' '.$check->check_no" :hotel="$hotel">
<div class="small mb-2">{{ $check->outlet->name() }} · <span dir="ltr">{{ $check->business_date->toDateString() }} {{ $check->created_at->format('H:i') }}</span> · {{ $check->user->name }}</div>
<table class="table table-sm">
    <thead><tr><th>{{ __('Item') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
    <tbody>@foreach ($check->lines as $l)<tr><td>{{ $l->name }}</td><td class="text-end">{{ (float) $l->quantity }}</td><td class="text-end"><x-money :value="$l->amount" /></td></tr>@endforeach</tbody>
</table>
<table class="table table-sm table-borderless w-75 ms-auto">
    <tr><th>{{ __('Subtotal') }}</th><td class="text-end"><x-money :value="$check->subtotal" /></td></tr>
    <tr><th>{{ __('Service') }}</th><td class="text-end"><x-money :value="$check->service_amount" /></td></tr>
    <tr><th>{{ __('Tax') }}</th><td class="text-end"><x-money :value="$check->tax_amount" /></td></tr>
    <tr class="fs-5"><th>{{ __('Total') }}</th><td class="text-end"><x-money :value="$check->total" /></td></tr>
</table>
<div class="small">
    @if ($check->settlement === 'room')
        {{ __('Charged to room') }} {{ $check->account?->stay?->room?->room_number }} · {{ $check->account?->name }}
        <div class="mt-4">{{ __('Guest signature') }} ________</div>
    @else
        {{ __('Paid by') }} {{ $check->method?->name() }} @if ($check->customer_name)· {{ $check->customer_name }}@endif
    @endif
</div>
</x-layouts.print>
