<x-layouts.app :title="$guest->full_name">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ $guest->full_name }}
        @if ($guest->is_vip)<span class="badge text-bg-warning fs-6">VIP</span>@endif
        @if ($guest->is_blacklisted)<span class="badge text-bg-danger fs-6">{{ __('Blacklisted') }}</span>@endif</h2>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="{{ route('guests.edit', $guest) }}">{{ __('Edit') }}</a>
        <a class="btn btn-primary" href="{{ route('reservations.create', ['guest_id' => $guest->id]) }}">+ {{ __('New reservation') }}</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-4"><div class="card card-body">
        <dl class="row mb-0 small">
            <dt class="col-5">{{ __('Phone') }}</dt><dd class="col-7" dir="ltr">{{ $guest->phone }}</dd>
            <dt class="col-5">{{ __('Email') }}</dt><dd class="col-7">{{ $guest->email }}</dd>
            <dt class="col-5">{{ __('Nationality') }}</dt><dd class="col-7">{{ $guest->nationality }}</dd>
            <dt class="col-5">{{ __('ID number') }}</dt><dd class="col-7">{{ $guest->id_type ? __(ucfirst(str_replace('_', ' ', $guest->id_type))) : '' }} {{ $guest->id_number }}</dd>
            <dt class="col-5">{{ __('Address') }}</dt><dd class="col-7">{{ $guest->address }}</dd>
            <dt class="col-5">{{ __('Notes') }}</dt><dd class="col-7">{{ $guest->notes }}</dd>
        </dl>
    </div></div>
    <div class="col-lg-8">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>{{ __('Reservation') }}</th><th>{{ __('Arrival') }}</th><th>{{ __('Departure') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                @forelse ($guest->reservations as $r)
                    <tr><td><a href="{{ route('reservations.show', $r) }}">{{ $r->reservation_no }}</a></td><td dir="ltr">{{ $r->arrival_date->toDateString() }}</td><td dir="ltr">{{ $r->departure_date->toDateString() }}</td><td><x-status :status="$r->status" /></td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">{{ __('No records.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layouts.app>
