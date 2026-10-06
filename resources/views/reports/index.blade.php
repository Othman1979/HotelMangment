<x-layouts.app :title="__('Reports')">
@php $titles = ['revenue' => 'Revenue by code', 'payments' => 'Payments', 'occupancy' => 'Occupancy & ADR', 'balances' => 'Open balances', 'outlets' => 'Outlet checks', 'in-house' => 'In-house guests']; @endphp
<h2 class="mb-3">{{ __('Reports') }}</h2>
<div class="row g-3">
    @foreach ($reports as $r)
        <div class="col-6 col-md-4"><a class="text-decoration-none text-reset" href="{{ route('reports.show', $r) }}"><div class="stat-card"><span class="stat-icon icon-blue"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/></svg></span><span class="fw-semibold">{{ __($titles[$r]) }}</span></div></a></div>
    @endforeach
</div>
</x-layouts.app>
