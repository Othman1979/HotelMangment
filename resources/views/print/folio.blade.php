<x-layouts.print :title="__('Guest folio')" :hotel="$hotel">
<div class="d-flex justify-content-between small mb-3">
    <div>{{ $account->name }}<br>{{ __('Account') }} {{ $account->account_no }}</div>
    @if ($account->stay)<div class="text-end">{{ __('Room') }} {{ $account->stay->room?->room_number }}<br><span dir="ltr">{{ $account->stay->arrival_date->toDateString() }} → {{ $account->stay->departure_date->toDateString() }}</span></div>@endif
</div>
@include('print._lines')
<div class="d-flex justify-content-between fs-5 fw-semibold"><span>{{ __('Balance') }}</span><x-money :value="$account->balance" /></div>
</x-layouts.print>
