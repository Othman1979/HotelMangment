@php use App\Enums\HousekeepingStatus as HK; use App\Enums\ServiceStatus as SS; @endphp
<x-layouts.app :title="__('Housekeeping')">
<x-page-head :title="__('Housekeeping')" :subtitle="__('Update room cleaning status and block rooms for maintenance.')" icon="broom">
    <div class="btn-group">
        @foreach (['' => 'All', 'dirty' => 'Dirty', 'occupied' => 'Occupied', 'ooo' => 'Out of order'] as $k => $l)
            <a class="btn btn-sm {{ (string) $filter === $k ? 'btn-primary' : 'btn-light' }}" href="{{ route('housekeeping.index', $k ? ['filter' => $k] : []) }}">{{ __($l) }}</a>
        @endforeach
    </div>
</x-page-head>
<div class="hk-summary">
    @foreach ([['clean', 'Clean', 'icon-green'], ['inspected', 'Inspected', 'icon-blue'], ['dirty', 'Dirty', 'icon-red'], ['occupied', 'Occupied', 'icon-amber'], ['ooo', 'Out of order', 'icon-slate']] as [$k, $l, $c])
        <div class="stat-card py-2"><span class="stat-icon {{ $c }}" style="width:36px;height:36px"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/></svg></span><span><span class="stat-value d-block fs-5">{{ $summary[$k] }}</span><span class="stat-label">{{ __($l) }}</span></span></div>
    @endforeach
</div>
<div class="row g-3">
    @forelse ($rooms as $room)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="room-tile hk-{{ $room->housekeeping_status->value }} {{ $room->blocks->isNotEmpty() ? 'is-blocked' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="num">{{ $room->room_number }}</span>
                    <x-room-code :room="$room" />
                </div>
                <div class="small text-muted mb-2">{{ $room->roomType->name() }} · {{ $room->occupancy_status->label() }}</div>
                @foreach ($room->blocks as $b)
                    <div class="small text-danger d-flex justify-content-between align-items-center mb-1">
                        <span>{{ $b->type->label() }} <span dir="ltr">{{ $b->from_date->format('m-d') }}→{{ $b->to_date->format('m-d') }}</span> · {{ $b->reason }}</span>
                        <form method="post" action="{{ route('housekeeping.release', $b) }}">@csrf<button class="btn btn-sm btn-link p-0">{{ __('Release') }}</button></form>
                    </div>
                @endforeach
                <form method="post" action="{{ route('housekeeping.status', $room) }}" class="btn-group btn-group-sm w-100">
                    @csrf
                    @foreach (HK::cases() as $s)
                        <button name="status" value="{{ $s->value }}" class="btn {{ $room->housekeeping_status === $s ? 'btn-'.$s->color() : 'btn-light' }}">{{ $s->label() }}</button>
                    @endforeach
                </form>
                <button class="btn btn-sm btn-link px-0 mt-2 align-self-start text-danger" data-bs-toggle="modal" data-bs-target="#blockModal" data-action="{{ route('housekeeping.block', $room) }}" data-room="{{ $room->room_number }}">{{ __('Block room') }}</button>
            </div>
        </div>
    @empty
        <p class="text-muted">{{ __('No records.') }}</p>
    @endforelse
</div>
<div class="modal fade" id="blockModal" tabindex="-1">
    <div class="modal-dialog"><form method="post" class="modal-content" id="blockForm">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ __('Block room') }} <span id="blockRoom"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-2">
            <div class="col-12"><select name="type" class="form-select"><option value="{{ SS::OutOfOrder->value }}">{{ SS::OutOfOrder->label() }}</option><option value="{{ SS::OutOfService->value }}">{{ SS::OutOfService->label() }}</option></select></div>
            <div class="col-6"><label class="form-label">{{ __('From') }}</label><input type="date" name="from_date" value="{{ $today->toDateString() }}" min="{{ $today->toDateString() }}" class="form-control" required></div>
            <div class="col-6"><label class="form-label">{{ __('To') }}</label><input type="date" name="to_date" value="{{ $today->toDateString() }}" class="form-control" required></div>
            <div class="col-12"><label class="form-label">{{ __('Reason') }}</label><input name="reason" class="form-control" required maxlength="255"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-danger">{{ __('Block room') }}</button></div>
    </form></div>
</div>
<x-slot:scripts>
<script>
document.getElementById('blockModal').addEventListener('show.bs.modal', e => {
    document.getElementById('blockForm').action = e.relatedTarget.dataset.action;
    document.getElementById('blockRoom').textContent = e.relatedTarget.dataset.room;
});
</script>
</x-slot:scripts>
</x-layouts.app>
