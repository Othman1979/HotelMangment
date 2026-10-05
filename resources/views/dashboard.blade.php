@php use App\Enums\Role; @endphp
<x-layouts.app :title="__('Dashboard')">
@php
    $user = auth()->user();
    $frontOffice = $user->hasRole(Role::Manager, Role::FrontDesk, Role::Cashier);
    $finance = $user->hasRole(Role::Manager, Role::NightAuditor, Role::Cashier);
    $occupancy = $stats['rooms'] ? (int) round($stats['occupied'] * 100 / $stats['rooms']) : 0;
    $svg = [
        'in' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
        'out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'bed' => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/>',
        'rack' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'ok' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'broom' => '<path d="m13 11 8-8M9.5 10.5l4 4M3 21l3.5-8.5 5 5z"/>',
        'x' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
        'add' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'walk' => '<circle cx="13" cy="4" r="2"/><path d="m9 20 3-6 3 3v4M6 12l3-4 4 1 3 3 3 1"/>',
        'pos' => '<path d="M3 11h18M5 11V7a7 7 0 0 1 14 0v4M2 15h20l-2 5H4z"/>',
        'wallet' => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4M3 5v14a2 2 0 0 0 2 2h15V7"/><circle cx="16" cy="14" r="1.5"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'reports' => '<path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/>',
    ];
    $icon = fn ($k) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$svg[$k].'</svg>';
    $cards = [];
    if ($frontOffice) {
        $cards[] = ['in', 'Arrivals', $stats['arrivals'], 'icon-blue', route('front.index', 'arrivals')];
        $cards[] = ['bed', 'In-house', $stats['in_house'], 'icon-green', route('front.index', 'in-house')];
        $cards[] = ['out', 'Departures', $stats['departures'], 'icon-amber', route('front.index', 'departures')];
    }
    $cards[] = ['ok', 'Vacant ready', $stats['vacant_ready'], 'icon-cyan', route('housekeeping.index')];
    $cards[] = ['broom', 'Dirty rooms', $stats['dirty'], 'icon-red', route('housekeeping.index', ['filter' => 'dirty'])];
    $cards[] = ['x', 'Out of order', $stats['ooo'], 'icon-slate', route('housekeeping.index', ['filter' => 'ooo'])];
    $actions = [];
    if ($frontOffice) {
        $actions[] = ['add', 'New reservation', route('reservations.create'), 'icon-blue'];
        $actions[] = ['walk', 'Walk-in', route('reservations.create', ['walk_in' => 1]), 'icon-gold'];
        $actions[] = ['wallet', 'Accounts', route('accounts.index'), 'icon-green'];
    }
    if ($user->hasRole(Role::Manager, Role::FrontDesk, Role::Cashier, Role::Outlet)) {
        $actions[] = ['pos', 'Outlets POS', route('pos.index'), 'icon-amber'];
    }
    $actions[] = ['rack', 'Room rack', route('rack'), 'icon-cyan'];
    if ($user->hasRole(Role::Manager, Role::Housekeeping, Role::FrontDesk)) {
        $actions[] = ['broom', 'Housekeeping', route('housekeeping.index'), 'icon-red'];
    }
    if ($user->hasRole(Role::Manager, Role::NightAuditor)) {
        $actions[] = ['moon', 'Night audit', route('night-audit.index'), 'icon-slate'];
    }
    if ($finance) {
        $actions[] = ['reports', 'Reports', route('reports.index'), 'icon-blue'];
    }
@endphp

<div class="hero-banner d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <p class="mb-1">{{ __('Welcome back') }}، {{ $user->name }}</p>
        <h2>{{ $hotel->name() }}</h2>
        <p>{{ __('Business date') }} <strong class="text-white" dir="ltr">{{ $today->toDateString() }}</strong> · {{ $today->translatedFormat('l') }}</p>
        @if ($frontOffice)
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a class="btn btn-gold" href="{{ route('reservations.create') }}">{!! $icon('add') !!}{{ __('New reservation') }}</a>
                <a class="btn btn-light" href="{{ route('reservations.create', ['walk_in' => 1]) }}">{!! $icon('walk') !!}{{ __('Walk-in') }}</a>
                <a class="btn btn-light" href="{{ route('front.index', 'arrivals') }}">{!! $icon('in') !!}{{ __('Arrivals') }}</a>
            </div>
        @endif
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="occ-ring" style="--p: {{ $occupancy }}"><div><span><strong>{{ $occupancy }}%</strong><small>{{ __('Occupancy') }}</small></span></div></div>
        <div class="small text-white-50 lh-lg">
            <div><strong class="text-white">{{ $stats['occupied'] }}</strong> / {{ $stats['rooms'] }} {{ __('rooms occupied') }}</div>
            <div><strong class="text-white">{{ $stats['vacant_ready'] }}</strong> {{ __('Vacant ready') }}</div>
        </div>
    </div>
</div>

@if (! $shift && $user->hasRole(Role::Manager, Role::FrontDesk, Role::Cashier, Role::Outlet))
    <div class="alert alert-warning d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span>{{ __('You have no open cashier shift. Payments and cash outlet sales need an open shift.') }}</span>
        <a class="btn btn-sm btn-warning" href="{{ route('shifts.index') }}">{{ __('Open shift') }}</a>
    </div>
@endif

<div class="row g-3 mb-4">
    @foreach ($cards as [$ic, $label, $value, $color, $url])
        <div class="col-6 col-md-4 col-xl-2">
            <a class="text-decoration-none text-reset" href="{{ $url }}">
                <div class="stat-card">
                    <span class="stat-icon {{ $color }}">{!! $icon($ic) !!}</span>
                    <span><span class="stat-value d-block">{{ $value }}</span><span class="stat-label">{{ __($label) }}</span></span>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="section-title">{{ __('Quick actions') }}</div>
<div class="quick-actions mb-4">
    @foreach ($actions as [$ic, $label, $url, $color])
        <a class="quick-action" href="{{ $url }}"><span class="stat-icon {{ $color }}">{!! $icon($ic) !!}</span>{{ __($label) }}</a>
    @endforeach
</div>

<div class="row g-3">
    @if ($frontOffice)
        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Arrivals today') }} <a class="small fw-normal" href="{{ route('front.index', 'arrivals') }}">{{ __('View all') }}</a></div>
                <div class="list-group list-group-flush mini-list">
                    @forelse ($arrivalsList as $s)
                        <a class="list-group-item list-group-item-action" href="{{ route('stays.check-in', $s) }}">
                            <span class="initials">{{ mb_substr($s->reservation->guest->full_name, 0, 1) }}</span>
                            <span class="flex-fill min-w-0"><span class="d-block fw-semibold text-truncate">{{ $s->reservation->guest->full_name }}</span><small class="text-muted">{{ $s->reservation->reservation_no }} · {{ $s->roomType->name() }}</small></span>
                            <span class="badge text-bg-primary">{{ $s->room?->room_number ?? __('No room') }}</span>
                        </a>
                    @empty
                        <div class="empty-state">{!! $icon('in') !!}<div>{{ __('No arrivals left today.') }}</div></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Departures today') }} <a class="small fw-normal" href="{{ route('front.index', 'departures') }}">{{ __('View all') }}</a></div>
                <div class="list-group list-group-flush mini-list">
                    @forelse ($departuresList as $s)
                        <a class="list-group-item list-group-item-action" href="{{ $s->account ? route('accounts.show', $s->account) : route('reservations.show', $s->reservation_id) }}">
                            <span class="initials">{{ $s->room?->room_number }}</span>
                            <span class="flex-fill min-w-0"><span class="d-block fw-semibold text-truncate">{{ $s->reservation->guest->full_name }}</span><small class="text-muted">{{ $s->reservation->reservation_no }}</small></span>
                            @if ($s->account)<x-money :value="$s->account->balance" :sign="true" />@endif
                        </a>
                    @empty
                        <div class="empty-state">{!! $icon('out') !!}<div>{{ __('No departures left today.') }}</div></div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
    <div class="col-lg-6 col-xxl-4">
        <div class="card h-100">
            <div class="card-header">{{ __('Availability tonight') }}</div>
            <div class="card-body">
                @foreach ($availability as $row)
                    @php $total = max(1, $typeTotals[$row['type']->id] ?? 0); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold">{{ $row['type']->name() }} <span class="text-muted fw-normal">· <x-money :value="$row['type']->base_rate" /></span></span>
                            <span><strong>{{ $row['available'] }}</strong> <span class="text-muted">/ {{ $total }}</span></span>
                        </div>
                        <div class="avail-bar"><span style="width: {{ min(100, round($row['available'] * 100 / $total)) }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @if ($finance)
        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Last night audit') }} @if ($lastAudit)<span class="badge text-bg-light" dir="ltr">{{ $lastAudit->business_date->toDateString() }}</span>@endif</div>
                <div class="card-body">
                    @if ($lastAudit)
                        <dl class="kv">
                            <dt>{{ __('Occupancy') }}</dt><dd>{{ $lastAudit->occupancyPercent() }}%</dd>
                            <dt>{{ __('ADR') }}</dt><dd><x-money :value="$lastAudit->adr()" /></dd>
                            <dt>{{ __('Room revenue') }}</dt><dd><x-money :value="$lastAudit->room_revenue" /></dd>
                            <dt>{{ __('Other revenue') }}</dt><dd><x-money :value="$lastAudit->other_revenue" /></dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">{{ __('No night audit has been run yet.') }}</p>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-between"><span>{{ __('Open accounts balance') }}</span><x-money :value="$stats['open_balance']" class="fw-bold" /></div>
            </div>
        </div>
    @endif
</div>
</x-layouts.app>
