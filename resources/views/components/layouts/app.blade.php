@php
    use App\Enums\Role;
    $user = auth()->user();
    $hotel = $user ? \App\Models\HotelSetting::current() : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <x-head :title="$title ?? null" />
</head>
<body class="{{ $user ? 'has-pane' : '' }}">
    <header class="titlebar">
        @if ($user)
            <button type="button" class="titlebar-btn" id="paneToggle" aria-label="{{ __('Menu') }}" title="{{ __('Menu') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        @endif
        <a class="titlebar-brand" href="{{ route('home') }}">
            <span class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h.01M15 7h.01M9 11h.01M15 11h.01M10 21v-4h4v4"/></svg>
            </span>
            <span class="titlebar-name">{{ $hotel?->name() ?? __('Hotel Management') }}</span>
        </a>
        <div class="titlebar-actions">
            @if ($user)
                <span class="badge text-bg-light border me-1" title="{{ __('Business date') }}">{{ __('Business date') }}: <span dir="ltr">{{ $hotel->business_date->toDateString() }}</span></span>
            @endif
            <x-culture-switcher />
            @if ($user)
                <div class="dropdown">
                    <button type="button" class="titlebar-btn titlebar-user" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $user->name }}">
                        <span class="avatar">{{ $user->initial() }}</span>
                        <span class="titlebar-username">{{ $user->name }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="px-3 py-2">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="small text-muted">{{ $user->username }} · {{ $user->role->label() }}</div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('shifts.index') }}">{{ __('My shift') }}</a>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="dropdown-item">{{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </header>

    @if ($user)
        <nav class="navpane" id="navPane" aria-label="{{ __('Menu') }}">
            <x-nav-item route="home" :label="__('Dashboard')" icon="home" />
            <x-nav-item route="rack" :label="__('Room rack')" icon="rack" />
            @if ($user->hasRole(Role::Manager, Role::FrontDesk, Role::Cashier))
                <div class="navpane-header">{{ __('Front office') }}</div>
                <x-nav-item route="reservations.create" :label="__('New reservation')" icon="add" />
                <x-nav-item route="reservations.index" :label="__('Reservations')" icon="calendar" active="reservations.index|reservations.show" />
                <x-nav-item route="front.index" :params="['list' => 'arrivals']" :label="__('Arrivals')" icon="in" />
                <x-nav-item route="front.index" :params="['list' => 'in-house']" :label="__('In-house')" icon="bed" />
                <x-nav-item route="front.index" :params="['list' => 'departures']" :label="__('Departures')" icon="out" />
                <x-nav-item route="guests.index" :label="__('Guests')" icon="users" active="guests.*" />
                <div class="navpane-header">{{ __('Cashiering') }}</div>
                <x-nav-item route="accounts.index" :label="__('Accounts')" icon="wallet" active="accounts.*" />
            @endif
            @if ($user->hasRole(Role::Manager, Role::FrontDesk, Role::Cashier, Role::Outlet))
                <x-nav-item route="shifts.index" :label="__('Shifts')" icon="cash" active="shifts.*" />
                <x-nav-item route="pos.index" :label="__('Outlets POS')" icon="pos" active="pos.*" />
            @endif
            @if ($user->hasRole(Role::Manager, Role::Housekeeping, Role::FrontDesk))
                <div class="navpane-header">{{ __('Rooms') }}</div>
                <x-nav-item route="housekeeping.index" :label="__('Housekeeping')" icon="broom" />
            @endif
            @if ($user->hasRole(Role::Manager, Role::NightAuditor, Role::Cashier))
                <div class="navpane-header">{{ __('Back office') }}</div>
                @if ($user->hasRole(Role::Manager, Role::NightAuditor))
                    <x-nav-item route="night-audit.index" :label="__('Night audit')" icon="moon" />
                @endif
                <x-nav-item route="reports.index" :label="__('Reports')" icon="reports" active="reports.*" />
            @endif
            @if ($user->hasRole(Role::Manager))
                <div class="navpane-header">{{ __('Setup') }}</div>
                <x-nav-item route="settings.edit" :label="__('Hotel settings')" icon="gear" />
                @foreach (['room-types' => 'Room types', 'rooms' => 'Rooms', 'transaction-codes' => 'Transaction codes', 'payment-methods' => 'Payment methods', 'outlets' => 'Outlets', 'outlet-items' => 'Outlet items', 'companies' => 'Companies', 'users' => 'Users'] as $key => $label)
                    <x-nav-item route="setup.index" :params="['resource' => $key]" :label="__($label)" icon="list" />
                @endforeach
            @endif
        </nav>
        <div class="navpane-backdrop" id="paneBackdrop"></div>
    @endif

    <div class="page">
        <main role="main" class="page-main">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    <script src="{{ asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/navpane.js') }}"></script>
    {{ $scripts ?? '' }}
</body>
</html>
