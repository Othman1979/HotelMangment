<x-layouts.app :title="__('Room rack')">
@php $today = \App\Models\HotelSetting::businessDate(); @endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Room rack') }}</h2>
    <form class="d-flex gap-2" method="get">
        <a class="btn btn-light" href="{{ route('rack', ['from' => $from->subDays(7)->toDateString()]) }}">&laquo;</a>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control" onchange="this.form.submit()">
        <a class="btn btn-light" href="{{ route('rack', ['from' => $from->addDays(7)->toDateString()]) }}">&raquo;</a>
    </form>
</div>
<div class="table-responsive mb-3">
    <table class="table table-bordered rack">
        <thead>
        <tr>
            <th>{{ __('Room') }}</th>
            @for ($i = 0; $i < $days; $i++)
                @php $d = $from->addDays($i); @endphp
                <th class="text-center {{ $d->isSameDay($today) ? 'today' : '' }}">{{ $d->format('d') }}<br><small class="text-muted">{{ $d->translatedFormat('D') }}</small></th>
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
                    <td class="cell {{ $d->isSameDay($today) && ! $stay ? 'today' : '' }}" @if ($stay) colspan="{{ $span }}" @endif>
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
