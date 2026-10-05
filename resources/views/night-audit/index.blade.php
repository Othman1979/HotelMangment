<x-layouts.app :title="__('Night audit')">
@php $blocked = $checks['open_shifts']->isNotEmpty() || $checks['due_out']->isNotEmpty(); @endphp
<h2 class="mb-3">{{ __('Night audit') }} · <span dir="ltr">{{ $date->toDateString() }}</span></h2>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">{{ __('Pre-audit checks') }}</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <div class="d-flex justify-content-between"><span>{{ __('Open cashier shifts') }}</span><span class="badge text-bg-{{ $checks['open_shifts']->isEmpty() ? 'success' : 'danger' }}">{{ $checks['open_shifts']->count() }}</span></div>
                    @foreach ($checks['open_shifts'] as $s)<div class="small"><a href="{{ route('shifts.show', $s) }}">{{ $s->shift_no }}</a> · {{ $s->user->name }}</div>@endforeach
                </li>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between"><span>{{ __('Guests due out not checked out') }}</span><span class="badge text-bg-{{ $checks['due_out']->isEmpty() ? 'success' : 'danger' }}">{{ $checks['due_out']->count() }}</span></div>
                    @foreach ($checks['due_out'] as $s)<div class="small">{{ $s->room->room_number }} · {{ $s->reservation->guest->full_name }} @if ($s->account)<a href="{{ route('accounts.show', $s->account) }}">{{ __('Account') }}</a>@endif</div>@endforeach
                </li>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between"><span>{{ __('Arrivals not checked in (will become no-show)') }}</span><span class="badge text-bg-{{ $checks['arrivals']->isEmpty() ? 'success' : 'warning' }}">{{ $checks['arrivals']->count() }}</span></div>
                    @foreach ($checks['arrivals'] as $s)<div class="small"><a href="{{ route('reservations.show', $s->reservation_id) }}">{{ $s->reservation->reservation_no }}</a> · {{ $s->reservation->guest->full_name }}</div>@endforeach
                </li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('In-house rooms to charge') }}</span><span class="badge text-bg-primary">{{ $checks['in_house']->count() }}</span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="post" action="{{ route('night-audit.run') }}" class="card card-body">
            @csrf
            <p>{{ __('The night audit posts room charges for every in-house guest, marks missing arrivals as no-show, marks occupied rooms dirty, saves the daily statistics and moves the business date to the next day.') }}</p>
            <div class="form-check mb-3"><input type="checkbox" name="confirm" value="1" id="confirm" class="form-check-input" required><label for="confirm" class="form-check-label">{{ __('I confirm that the day is complete.') }}</label></div>
            <button class="btn btn-primary" @disabled($blocked)>{{ __('Run night audit') }}</button>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Occupancy') }}</th><th>{{ __('ADR') }}</th><th>{{ __('RevPAR') }}</th><th class="text-end">{{ __('Room revenue') }}</th><th class="text-end">{{ __('Other revenue') }}</th><th class="text-end">{{ __('Payments') }}</th><th>{{ __('No-shows') }}</th><th>{{ __('User') }}</th></tr></thead>
        <tbody>
        @forelse ($audits as $a)
            <tr><td dir="ltr">{{ $a->business_date->toDateString() }}</td><td>{{ $a->rooms_occupied }}/{{ $a->rooms_total }} ({{ $a->occupancyPercent() }}%)</td><td><x-money :value="$a->adr()" /></td><td><x-money :value="$a->revpar()" /></td>
                <td class="text-end"><x-money :value="$a->room_revenue" /></td><td class="text-end"><x-money :value="$a->other_revenue" /></td><td class="text-end"><x-money :value="$a->payments_total" /></td><td>{{ $a->no_shows }}</td><td>{{ $a->user->name }}</td></tr>
        @empty
            <tr><td colspan="9" class="text-muted text-center">{{ __('No night audit has been run yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</x-layouts.app>
