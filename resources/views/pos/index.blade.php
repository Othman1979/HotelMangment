<x-layouts.app :title="__('Outlets POS')">
<h2 class="mb-3">{{ __('Outlets POS') }}</h2>
<div class="row g-3 mb-4">
    @foreach ($outlets as $o)
        <div class="col-6 col-md-4 col-xl-3">
            <a class="text-decoration-none text-reset" href="{{ route('pos.show', $o) }}">
                <div class="stat-card">
                    <span class="stat-icon icon-blue"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18M5 11V7a7 7 0 0 1 14 0v4M2 15h20l-2 5H4z"/></svg></span>
                    <span><span class="fw-semibold d-block">{{ $o->name() }}</span><span class="stat-label">{{ $o->items_count }} {{ __('items') }}</span></span>
                </div>
            </a>
        </div>
    @endforeach
</div>
<div class="card">
    <div class="card-header">{{ __('Checks today') }} <span dir="ltr">{{ $today->toDateString() }}</span></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead><tr><th>{{ __('Check') }}</th><th>{{ __('Outlet') }}</th><th>{{ __('Settlement') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('Time') }}</th></tr></thead>
            <tbody>
            @forelse ($checks as $c)
                <tr><td><a href="{{ route('pos.check', $c) }}" target="_blank">{{ $c->check_no }}</a></td><td>{{ $c->outlet->name() }}</td>
                    <td>@if ($c->settlement === 'room'){{ __('Room') }} {{ $c->account?->stay?->room?->room_number }}@else{{ $c->method?->name() }}@endif</td>
                    <td class="text-end"><x-money :value="$c->total" /></td><td>{{ $c->created_at->format('H:i') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center">{{ __('No records.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.app>
