<x-layouts.app :title="__('Reservations')">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Reservations') }}</h2>
    <a class="btn btn-primary" href="{{ route('reservations.create') }}">+ {{ __('New reservation') }}</a>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Reservation no., guest or phone') }}"></div>
    <div class="col-md-3"><select name="status" class="form-select"><option value="">{{ __('All statuses') }}</option>
        @foreach (\App\Enums\ReservationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>@endforeach
    </select></div>
    <div class="col-md-3"><input type="date" name="date" value="{{ request('date') }}" class="form-control" title="{{ __('Staying on date') }}"></div>
    <div class="col-md-2"><button class="btn btn-light w-100">{{ __('Search') }}</button></div>
</form>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>{{ __('No.') }}</th><th>{{ __('Guest') }}</th><th>{{ __('Arrival') }}</th><th>{{ __('Departure') }}</th><th>{{ __('Rooms') }}</th><th>{{ __('Source') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse ($reservations as $r)
            <tr>
                <td><a href="{{ route('reservations.show', $r) }}">{{ $r->reservation_no }}</a></td>
                <td>{{ $r->guest->full_name }} @if ($r->company)<small class="text-muted">· {{ $r->company->name }}</small>@endif</td>
                <td dir="ltr">{{ $r->arrival_date->toDateString() }}</td><td dir="ltr">{{ $r->departure_date->toDateString() }}</td>
                <td>{{ $r->rooms->map(fn ($s) => $s->room?->room_number ?? '—')->join(', ') }}</td>
                <td>{{ __(str_replace('_', ' ', ucfirst($r->source))) }}</td>
                <td><x-status :status="$r->status" /></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $reservations->links() }}</div>
</x-layouts.app>
