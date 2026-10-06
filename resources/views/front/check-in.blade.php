<x-layouts.app :title="__('Check-in')">
<h2 class="mb-3">{{ __('Check-in') }} · {{ $stay->reservation->guest->full_name }}</h2>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-body">
            <dl class="row mb-0 small">
                <dt class="col-5">{{ __('Reservation') }}</dt><dd class="col-7"><a href="{{ route('reservations.show', $stay->reservation_id) }}">{{ $stay->reservation->reservation_no }}</a></dd>
                <dt class="col-5">{{ __('Room type') }}</dt><dd class="col-7">{{ $stay->roomType->name() }}</dd>
                <dt class="col-5">{{ __('Arrival') }}</dt><dd class="col-7" dir="ltr">{{ $stay->arrival_date->toDateString() }}</dd>
                <dt class="col-5">{{ __('Departure') }}</dt><dd class="col-7" dir="ltr">{{ $stay->departure_date->toDateString() }}</dd>
                <dt class="col-5">{{ __('Nightly rate') }}</dt><dd class="col-7"><x-money :value="$stay->nightly_rate" /></dd>
                <dt class="col-5">{{ __('ID number') }}</dt><dd class="col-7">{{ $stay->reservation->guest->id_number ?: '—' }}</dd>
            </dl>
            @if (! $stay->arrival_date->isSameDay($today))
                <div class="alert alert-warning mt-3 mb-0">{{ __('Arrival date must be the business date (:date). Change the dates first.', ['date' => $today->toDateString()]) }}</div>
            @endif
            @if ($stay->reservation->guest->is_blacklisted)
                <div class="alert alert-danger mt-3 mb-0">{{ __('This guest is blacklisted.') }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-7">
        <form method="post" action="{{ route('stays.check-in', $stay) }}" class="card">
            @csrf
            <div class="card-header">{{ __('Choose a room') }}</div>
            <div class="card-body">
                <div class="row g-2">
                    @forelse ($rooms as $room)
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="room_id" id="room{{ $room->id }}" value="{{ $room->id }}" @checked($room->id === $stay->room_id) @disabled(! $room->isReadyForCheckIn()) required>
                            <label class="btn btn-outline-primary w-100" for="room{{ $room->id }}">{{ $room->room_number }}<br><x-room-code :room="$room" /></label>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No free rooms of this type for these dates.') }}</p>
                    @endforelse
                </div>
                <p class="small text-muted mt-2 mb-0">{{ __('Dirty or out-of-order rooms cannot be checked in.') }}</p>
            </div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Check-in and open account') }}</button></div>
        </form>
    </div>
</div>
</x-layouts.app>
