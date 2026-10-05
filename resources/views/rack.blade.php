<x-layouts.app :title="__('Room rack')">
@php $today = \App\Models\HotelSetting::businessDate(); @endphp
<x-page-head :title="__('Room rack')" :subtitle="__('14-day view of every room. Click a stay to open the reservation.')" icon="rack">
    <form class="d-flex gap-2" method="get">
        <a class="btn btn-light" href="{{ route('rack', ['from' => $from->subDays(7)->toDateString()]) }}">&laquo;</a>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control" onchange="this.form.submit()">
        <a class="btn btn-light" href="{{ route('rack', ['from' => $from->addDays(7)->toDateString()]) }}">&raquo;</a>
        <a class="btn btn-light" href="{{ route('rack') }}">{{ __('Today') }}</a>
    </form>
</x-page-head>
<div class="legend mb-2">
    <span><i style="background:#1d4ed8"></i>{{ __('Confirmed') }}</span>
    <span><i style="background:#94a3b8"></i>{{ __('Tentative') }}</span>
    <span><i style="background:#15803d"></i>{{ __('In-house') }}</span>
    <span><i style="background:#cbd5e1"></i>{{ __('Checked out') }}</span>
    <span><i style="background:#fffbeb;border:1px solid #f5d77a"></i>{{ __('Business date') }}</span>
</div>
<div class="table-responsive rack-wrap mb-3">
    <table class="table table-bordered rack">
        <thead>
        <tr>
            <th>{{ __('Room') }}</th>
            @for ($i = 0; $i < $days; $i++)
                @php $d = $from->addDays($i); @endphp
                <th class="text-center {{ $d->isSameDay($today) ? 'today' : ($d->isWeekend() ? 'weekend' : '') }}">{{ $d->format('d') }}<br><small class="text-muted">{{ $d->translatedFormat('D') }}</small></th>
            @endfor
        </tr>
        </thead>
        <tbody>
        @foreach ($rooms as $room)
            @php $roomStays = $stays->get($room->id, collect()); @endphp
            <tr>
                <th class="text-nowrap">{{ $room->room_number }} <x-room-code :room="$room" /><br><small class="text-muted fw-normal">{{ $room->roomType->code }}</small></th>
                @for ($i = 0; $i < $days; $i++)
                    @php
                        $d = $from->addDays($i);
                        $stay = $roomStays->first(fn ($s) => $s->arrival_date->lte($d) && $s->departure_date->gt($d));
                        $starts = $stay && ($stay->arrival_date->isSameDay($d) || $i === 0);
                        $span = $stay ? min($days - $i, (int) $d->diffInDays($stay->departure_date)) : 1;
                    @endphp
                    @if ($stay && ! $starts)
                        @continue
                    @endif
                    <td class="cell {{ ! $stay ? ($d->isSameDay($today) ? 'today' : ($d->isWeekend() ? 'weekend' : '')) : '' }}" @if ($stay) colspan="{{ $span }}" @endif>
                        @if ($stay)
                            <a class="stay stay-{{ $stay->status->value }}" href="{{ route('reservations.show', $stay->reservation_id) }}" title="{{ $stay->reservation->reservation_no }} - {{ $stay->reservation->guest->full_name }}">{{ $stay->reservation->guest->full_name }}</a>
                        @endif
                    </td>
                @endfor
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if ($unassigned->isNotEmpty())
    <div class="card">
        <div class="card-header">{{ __('Reservations without a room') }}</div>
        <div class="list-group list-group-flush">
            @foreach ($unassigned as $s)
                <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('reservations.show', $s->reservation_id) }}">
                    <span>{{ $s->reservation->reservation_no }} · {{ $s->reservation->guest->full_name }} · {{ $s->roomType->name() }}</span>
                    <span dir="ltr">{{ $s->arrival_date->toDateString() }} → {{ $s->departure_date->toDateString() }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
</x-layouts.app>
