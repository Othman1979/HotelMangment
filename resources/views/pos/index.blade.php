<x-layouts.app :title="__('Outlets POS')">
<x-page-head :title="__('Outlets POS')" :subtitle="__('Restaurant, café, room service, mini-bar and laundry sales.')" icon="pos" />
@php
    $looks = ['REST' => ['icon-amber', '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>'],
        'CAFE' => ['icon-gold', '<path d="M17 8h1a4 4 0 1 1 0 8h-1M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4zM6 2v2M10 2v2M14 2v2"/>'],
        'RS' => ['icon-blue', '<path d="M3 18h18M5 18a7 7 0 0 1 14 0M12 8V6M10 6h4"/>'],
        'MINI' => ['icon-cyan', '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M5 10h14M9 6v1M9 13v3"/>'],
        'LAUN' => ['icon-green', '<rect x="3" y="2" width="18" height="20" rx="2"/><circle cx="12" cy="13" r="5"/><path d="M7 6h.01M11 6h.01"/>']];
@endphp
<div class="row g-3 mb-4">
    @foreach ($outlets as $o)
        <div class="col-6 col-md-4 col-xl">
            <a class="text-decoration-none text-reset" href="{{ route('pos.show', $o) }}">
                <div class="stat-card">
                    @php [$cls, $path] = $looks[$o->code] ?? ['icon-blue', '<path d="M3 11h18M5 11V7a7 7 0 0 1 14 0v4M2 15h20l-2 5H4z"/>']; @endphp
                    <span class="stat-icon {{ $cls }}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg></span>
                    <span><span class="fw-semibold d-block">{{ $o->name() }}</span><span class="stat-label">{{ $o->items_count }} {{ __('items') }}</span></span>
                </div>
            </a>
        </div>
    @endforeach
</div>
<div class="card">
    <div class="card-header">{{ __('Checks today') }} <span dir="ltr">{{ $today->toDateString() }}</span></div>
    <div class="table-responsive">
        <table class="table table-hover">
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
