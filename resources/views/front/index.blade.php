@php
    $titles = ['arrivals' => 'Arrivals', 'in-house' => 'In-house', 'departures' => 'Departures'];
    use App\Enums\ReservationStatus as RS;
@endphp
<x-layouts.app :title="__($titles[$list])">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __($titles[$list]) }} <span class="badge text-bg-light border fs-6">{{ $stays->count() }}</span></h2>
    @if ($list === 'arrivals')<a class="btn btn-primary" href="{{ route('reservations.create', ['walk_in' => 1]) }}">{{ __('Walk-in') }}</a>@endif
</div>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>{{ __('Room') }}</th><th>{{ __('Guest') }}</th><th>{{ __('Reservation') }}</th><th>{{ __('Arrival') }}</th><th>{{ __('Departure') }}</th><th>{{ __('Rate') }}</th><th>{{ __('Balance') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($stays as $s)
            <tr>
                <td>{{ $s->room?->room_number ?? '—' }} <small class="text-muted">{{ $s->roomType->code }}</small></td>
                <td>{{ $s->reservation->guest->full_name }} @if ($s->reservation->company)<small class="text-muted">· {{ $s->reservation->company->name }}</small>@endif</td>
                <td><a href="{{ route('reservations.show', $s->reservation_id) }}">{{ $s->reservation->reservation_no }}</a></td>
                <td dir="ltr" class="{{ $s->arrival_date->lt($today) && $list === 'arrivals' ? 'text-danger' : '' }}">{{ $s->arrival_date->toDateString() }}</td>
                <td dir="ltr">{{ $s->departure_date->toDateString() }}</td>
                <td><x-money :value="$s->nightly_rate" /></td>
                <td>@if ($s->account)<a href="{{ route('accounts.show', $s->account) }}"><x-money :value="$s->account->balance" :sign="true" /></a>@endif</td>
                <td class="text-end text-nowrap">
                    @if ($list === 'arrivals')
                        @if ($s->arrival_date->isSameDay($today))
                            <a class="btn btn-sm btn-primary" href="{{ route('stays.check-in', $s) }}">{{ __('Check-in') }}</a>
                        @else
                            <span class="badge text-bg-danger">{{ __('Past arrival - no-show at night audit') }}</span>
                        @endif
                    @else
                        @if ($s->account)<a class="btn btn-sm btn-light" href="{{ route('accounts.show', $s->account) }}">{{ __('Account') }}</a>@endif
                        @if ($list === 'departures')
                            <a class="btn btn-sm btn-primary" href="{{ $s->account ? route('accounts.show', $s->account) : '#' }}#checkout">{{ __('Check-out') }}</a>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</x-layouts.app>
