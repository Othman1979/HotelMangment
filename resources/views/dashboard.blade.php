<x-layouts.app :title="__('Dashboard')">
@php
    $cards = [
        ['in', 'Arrivals', $stats['arrivals'], 'icon-blue', route('front.index', 'arrivals')],
        ['bed', 'In-house', $stats['in_house'], 'icon-green', route('front.index', 'in-house')],
        ['out', 'Departures', $stats['departures'], 'icon-amber', route('front.index', 'departures')],
        ['rack', 'Occupancy', $stats['rooms'] ? round($stats['occupied'] * 100 / $stats['rooms']).'%' : '0%', 'icon-cyan', route('rack')],
        ['ok', 'Vacant ready', $stats['vacant_ready'], 'icon-green', route('housekeeping.index')],
        ['broom', 'Dirty rooms', $stats['dirty'], 'icon-red', route('housekeeping.index', ['filter' => 'dirty'])],
        ['x', 'Out of order', $stats['ooo'], 'icon-slate', route('housekeeping.index', ['filter' => 'ooo'])],
    ];
    $svg = [
        'in' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
        'out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'bed' => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/>',
        'rack' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'ok' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'broom' => '<path d="m13 11 8-8M9.5 10.5l4 4M3 21l3.5-8.5 5 5z"/>',
        'x' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
    ];
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Dashboard') }} <small class="text-muted fs-6" dir="ltr">{{ $today->toDateString() }}</small></h2>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="{{ route('reservations.create', ['walk_in' => 1]) }}">{{ __('Walk-in') }}</a>
        <a class="btn btn-primary" href="{{ route('reservations.create') }}">+ {{ __('New reservation') }}</a>
    </div>
</div>

@unless ($shift)
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span>{{ __('You have no open cashier shift. Payments and cash outlet sales need an open shift.') }}</span>
        <a class="btn btn-sm btn-light" href="{{ route('shifts.index') }}">{{ __('Open shift') }}</a>
    </div>
@endunless

<div class="row g-3 mb-4">
    @foreach ($cards as [$icon, $label, $value, $color, $url])
        <div class="col-6 col-md-4 col-xl">
            <a class="text-decoration-none text-reset" href="{{ $url }}">
                <div class="stat-card">
                    <span class="stat-icon {{ $color }}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svg[$icon] !!}</svg></span>
                    <span><span class="stat-value d-block">{{ $value }}</span><span class="stat-label">{{ __($label) }}</span></span>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Availability tonight') }}</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('Room type') }}</th><th>{{ __('Base rate') }}</th><th>{{ __('Available') }}</th></tr></thead>
                    <tbody>
                    @foreach ($availability as $row)
                        <tr><td>{{ $row['type']->name() }}</td><td><x-money :value="$row['type']->base_rate" /></td><td><span class="badge text-bg-{{ $row['available'] ? 'success' : 'danger' }}">{{ $row['available'] }}</span></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Last night audit') }}</div>
            <div class="card-body">
                @if ($lastAudit)
                    <dl class="row mb-0">
                        <dt class="col-6">{{ __('Date') }}</dt><dd class="col-6" dir="ltr">{{ $lastAudit->business_date->toDateString() }}</dd>
                        <dt class="col-6">{{ __('Occupancy') }}</dt><dd class="col-6">{{ $lastAudit->occupancyPercent() }}%</dd>
                        <dt class="col-6">{{ __('ADR') }}</dt><dd class="col-6"><x-money :value="$lastAudit->adr()" /></dd>
                        <dt class="col-6">{{ __('Room revenue') }}</dt><dd class="col-6"><x-money :value="$lastAudit->room_revenue" /></dd>
                        <dt class="col-6">{{ __('Other revenue') }}</dt><dd class="col-6"><x-money :value="$lastAudit->other_revenue" /></dd>
                    </dl>
                @else
                    <p class="text-muted mb-0">{{ __('No night audit has been run yet.') }}</p>
                @endif
                <hr>
                <div class="d-flex justify-content-between"><span>{{ __('Open accounts balance') }}</span><x-money :value="$stats['open_balance']" class="fw-semibold" /></div>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
