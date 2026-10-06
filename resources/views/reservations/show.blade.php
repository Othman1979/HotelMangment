@php use App\Enums\ReservationStatus as RS; @endphp
<x-layouts.app :title="$reservation->reservation_no">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Reservation') }} {{ $reservation->reservation_no }} <x-status :status="$reservation->status" class="fs-6" /></h2>
    <div class="d-flex gap-2">
        @if ($reservation->status === RS::Tentative)
            <form method="post" action="{{ route('reservations.confirm', $reservation) }}">@csrf<button class="btn btn-light">{{ __('Confirm') }}</button></form>
        @endif
        @if (in_array($reservation->status, [RS::Confirmed, RS::Tentative], true))
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">{{ __('Cancel reservation') }}</button>
        @endif
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-body">
            <dl class="row mb-0 small">
                <dt class="col-5">{{ __('Guest') }}</dt><dd class="col-7"><a href="{{ route('guests.show', $reservation->guest) }}">{{ $reservation->guest->full_name }}</a> @if ($reservation->guest->is_blacklisted)<span class="badge text-bg-danger">{{ __('Blacklisted') }}</span>@endif</dd>
                <dt class="col-5">{{ __('Phone') }}</dt><dd class="col-7" dir="ltr">{{ $reservation->guest->phone }}</dd>
                <dt class="col-5">{{ __('Company') }}</dt><dd class="col-7">{{ $reservation->company?->name ?? '—' }}</dd>
                <dt class="col-5">{{ __('Arrival') }}</dt><dd class="col-7" dir="ltr">{{ $reservation->arrival_date->toDateString() }}</dd>
                <dt class="col-5">{{ __('Departure') }}</dt><dd class="col-7" dir="ltr">{{ $reservation->departure_date->toDateString() }}</dd>
                <dt class="col-5">{{ __('Nights') }}</dt><dd class="col-7">{{ $reservation->nights() }}</dd>
                <dt class="col-5">{{ __('Source') }}</dt><dd class="col-7">{{ __(ucfirst(str_replace('_', ' ', $reservation->source))) }}</dd>
                <dt class="col-5">{{ __('External reference') }}</dt><dd class="col-7">{{ $reservation->external_ref ?? '—' }}</dd>
                <dt class="col-5">{{ __('Created by') }}</dt><dd class="col-7">{{ $reservation->creator->name }} · {{ $reservation->created_at->format('Y-m-d H:i') }}</dd>
                @if ($reservation->cancel_reason)<dt class="col-5">{{ __('Cancel reason') }}</dt><dd class="col-7">{{ $reservation->cancel_reason }}</dd>@endif
                @if ($reservation->notes)<dt class="col-5">{{ __('Notes') }}</dt><dd class="col-7">{{ $reservation->notes }}</dd>@endif
            </dl>
        </div>
    </div>
    <div class="col-lg-8">
        @foreach ($reservation->rooms as $stay)
            @php $editable = in_array($stay->status, [RS::Confirmed, RS::Tentative, RS::CheckedIn], true); @endphp
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><strong>{{ $stay->room?->room_number ?? __('No room') }}</strong> · {{ $stay->roomType->name() }} · {{ $stay->adults }} {{ __('Adults') }}@if ($stay->children), {{ $stay->children }} {{ __('Children') }}@endif
                        · <x-money :value="$stay->nightly_rate" /> / {{ __('night') }} <x-status :status="$stay->status" /></span>
                    <span class="d-flex gap-2">
                        @if (in_array($stay->status, [RS::Confirmed, RS::Tentative], true) && $stay->arrival_date->isSameDay($today))
                            <a class="btn btn-sm btn-primary" href="{{ route('stays.check-in', $stay) }}">{{ __('Check-in') }}</a>
                        @endif
                        @if ($stay->account)
                            <a class="btn btn-sm btn-light" href="{{ route('accounts.show', $stay->account) }}">{{ __('Account') }} {{ $stay->account->account_no }} · <x-money :value="$stay->account->balance" /></a>
                        @elseif ($editable)
                            <a class="btn btn-sm btn-light" href="{{ route('accounts.index', ['q' => $reservation->reservation_no]) }}" hidden></a>
                        @endif
                    </span>
                </div>
                @if ($editable)
                    <div class="card-body">
                        <div class="row g-3">
                            <form method="post" action="{{ route('stays.dates', $stay) }}" class="col-md-6 d-flex gap-2 align-items-end">
                                @csrf
                                <div><label class="form-label small">{{ __('Arrival') }}</label><input type="date" name="arrival_date" value="{{ $stay->arrival_date->toDateString() }}" class="form-control form-control-sm" @readonly($stay->status === RS::CheckedIn)></div>
                                <div><label class="form-label small">{{ __('Departure') }}</label><input type="date" name="departure_date" value="{{ $stay->departure_date->toDateString() }}" class="form-control form-control-sm"></div>
                                <button class="btn btn-sm btn-light">{{ __('Change dates') }}</button>
                            </form>
                            @if ($stay->status !== RS::CheckedIn)
                                <form method="post" action="{{ route('stays.assign', $stay) }}" class="col-md-3 d-flex gap-2 align-items-end">
                                    @csrf
                                    <div class="flex-fill"><label class="form-label small">{{ __('Room') }}</label>
                                        <select name="room_id" class="form-select form-select-sm" required><option value=""></option>
                                            @foreach ($freeRooms[$stay->id] as $r)<option value="{{ $r->id }}" @selected($r->id === $stay->room_id)>{{ $r->room_number }} ({{ $r->statusCode() }})</option>@endforeach
                                        </select></div>
                                    <button class="btn btn-sm btn-light">{{ __('Assign') }}</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('stays.rate', $stay) }}" class="col-md-3 d-flex gap-2 align-items-end">
                                @csrf
                                <div><label class="form-label small">{{ __('Nightly rate') }}</label><input type="number" step="0.001" min="0" name="nightly_rate" value="{{ $stay->nightly_rate }}" class="form-control form-control-sm"></div>
                                <input type="hidden" name="reason" value="manual">
                                <button class="btn btn-sm btn-light">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
        @if ($history->isNotEmpty())
            <div class="card">
                <div class="card-header">{{ __('History') }}</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($history as $h)
                        <li class="list-group-item">{{ $h->created_at->format('Y-m-d H:i') }} · {{ $h->user?->name }} · {{ __($h->action) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog"><form method="post" action="{{ route('reservations.cancel', $reservation) }}" class="modal-content">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('Cancel reservation') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('Reason') }}</label><input name="reason" class="form-control" required maxlength="255"></div>
        <div class="modal-footer"><button class="btn btn-danger">{{ __('Cancel reservation') }}</button></div>
    </form></div>
</div>
</x-layouts.app>
