<x-layouts.app :title="$walkIn ? __('Walk-in') : __('New reservation')">
<h2 class="mb-3">{{ $walkIn ? __('Walk-in') : __('New reservation') }}</h2>
<form method="post" action="{{ route('reservations.store') }}" id="resForm">
    @csrf
    @if ($walkIn)<input type="hidden" name="check_in_now" value="1">@endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">{{ __('Guest') }}</div>
                <div class="card-body">
                    <input type="hidden" name="guest_id" id="guestId" value="{{ old('guest_id', $guest?->id) }}">
                    <div id="guestPicked" class="alert alert-info d-flex justify-content-between align-items-center {{ old('guest_id', $guest?->id) ? '' : 'd-none' }}">
                        <span id="guestPickedName">{{ $guest?->full_name }}</span>
                        <button type="button" class="btn btn-sm btn-light" id="guestClear">{{ __('Change') }}</button>
                    </div>
                    <div id="guestNew" class="{{ old('guest_id', $guest?->id) ? 'd-none' : '' }}">
                        <div class="position-relative mb-3">
                            <input id="guestSearch" class="form-control" placeholder="{{ __('Search existing guest by name or phone') }}" autocomplete="off">
                            <div id="guestResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 10"></div>
                        </div>
                        <div class="small text-muted mb-2">{{ __('Or enter a new guest:') }}</div>
                        @include('guests._fields', ['guest' => null, 'prefix' => 'guest'])
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Rooms') }}</span>
                    <button type="button" class="btn btn-sm btn-light" id="addRoom">+ {{ __('Add room') }}</button>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>{{ __('Room type') }}</th><th>{{ __('Room') }}</th><th style="width:6rem">{{ __('Adults') }}</th><th style="width:6rem">{{ __('Children') }}</th><th style="width:9rem">{{ __('Nightly rate') }}</th><th></th></tr></thead>
                        <tbody id="roomRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-body">
                <div class="mb-3"><label class="form-label">{{ __('Arrival') }}</label><input type="date" name="arrival_date" id="arrival" class="form-control" value="{{ old('arrival_date', $today->toDateString()) }}" min="{{ $today->toDateString() }}" @readonly($walkIn) required></div>
                <div class="mb-3"><label class="form-label">{{ __('Departure') }}</label><input type="date" name="departure_date" id="departure" class="form-control" value="{{ old('departure_date', $today->addDay()->toDateString()) }}" required></div>
                <div class="mb-3 small text-muted"><span id="nights">1</span> {{ __('nights') }}</div>
                <div class="mb-3"><label class="form-label">{{ __('Source') }}</label>
                    <select name="source" class="form-select">
                        @foreach (\App\Models\Reservation::SOURCES as $s)<option value="{{ $s }}" @selected(old('source', $walkIn ? 'walk_in' : 'phone') === $s)>{{ __(ucfirst(str_replace('_', ' ', $s))) }}</option>@endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="confirmed">{{ __('Confirmed') }}</option>
                        @unless ($walkIn)<option value="tentative" @selected(old('status') === 'tentative')>{{ __('Tentative') }}</option>@endunless
                    </select></div>
                <div class="mb-3"><label class="form-label">{{ __('Company') }}</label>
                    <select name="company_id" class="form-select"><option value=""></option>
                        @foreach ($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label">{{ __('External reference') }}</label><input name="external_ref" value="{{ old('external_ref') }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label">{{ __('Notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                <div class="d-flex justify-content-between mb-3"><span>{{ __('Estimated total') }}</span><strong id="estTotal" dir="ltr">0.000</strong></div>
                <button class="btn btn-primary w-100">{{ $walkIn ? __('Save and check in') : __('Save reservation') }}</button>
            </div>
        </div>
    </div>
</form>
<template id="rowTpl">
    <tr>
        <td><select class="form-select form-select-sm type" name="rooms[__i__][room_type_id]" required>
            @foreach ($roomTypes as $t)<option value="{{ $t->id }}" data-rate="{{ $t->base_rate }}">{{ $t->name() }}</option>@endforeach
        </select></td>
        <td><select class="form-select form-select-sm room" name="rooms[__i__][room_id]" {{ $walkIn ? 'required' : '' }}><option value="">{{ $walkIn ? '' : __('Assign later') }}</option></select></td>
        <td><input type="number" class="form-control form-control-sm" name="rooms[__i__][adults]" value="1" min="1" max="10" required></td>
        <td><input type="number" class="form-control form-control-sm" name="rooms[__i__][children]" value="0" min="0" max="10"></td>
        <td><input type="number" class="form-control form-control-sm rate" name="rooms[__i__][nightly_rate]" step="0.001" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-light remove">&times;</button></td>
    </tr>
</template>
<x-slot:scripts>
<script>
(function () {
    const rows = document.getElementById('roomRows'), tpl = document.getElementById('rowTpl').innerHTML;
    const arrival = document.getElementById('arrival'), departure = document.getElementById('departure');
    let idx = 0, avail = { types: [], rooms: [] };
    const fmt = n => (Math.round(n * 1000) / 1000).toFixed(3);
    function nights() { const d = (new Date(departure.value) - new Date(arrival.value)) / 864e5; return d > 0 ? d : 0; }
    function total() {
        let t = 0; rows.querySelectorAll('.rate').forEach(r => t += (parseFloat(r.value) || 0));
        document.getElementById('nights').textContent = nights();
        document.getElementById('estTotal').textContent = fmt(t * nights());
    }
    function fillRooms(tr) {
        const type = tr.querySelector('.type'), room = tr.querySelector('.room'), keep = room.value;
        const taken = [...rows.querySelectorAll('.room')].filter(r => r !== room).map(r => r.value);
        room.querySelectorAll('option:not(:first-child)').forEach(o => o.remove());
        avail.rooms.filter(r => r.type_id == type.value && !taken.includes(String(r.id))).forEach(r => room.add(new Option(r.number + ' (' + r.status + ')', r.id, false, String(r.id) === keep)));
        type.querySelectorAll('option').forEach(o => {
            const a = avail.types.find(t => t.id == o.value);
            o.textContent = o.textContent.replace(/ \[\d+\]$/, '') + (a ? ' [' + a.available + ']' : '');
        });
    }
    function addRow() {
        rows.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', idx++));
        const tr = rows.lastElementChild, type = tr.querySelector('.type'), rate = tr.querySelector('.rate');
        rate.value = type.selectedOptions[0]?.dataset.rate || 0;
        type.addEventListener('change', () => { rate.value = type.selectedOptions[0].dataset.rate; fillRooms(tr); total(); });
        tr.querySelector('.room').addEventListener('change', () => rows.querySelectorAll('tr').forEach(fillRooms));
        rate.addEventListener('input', total);
        tr.querySelector('.remove').addEventListener('click', () => { if (rows.children.length > 1) { tr.remove(); total(); } });
        fillRooms(tr); total();
    }
    async function refresh() {
        if (nights() <= 0) return total();
        const res = await fetch(@json(route('reservations.availability')) + '?from=' + arrival.value + '&to=' + departure.value, { headers: { Accept: 'application/json' } });
        if (res.ok) avail = await res.json();
        rows.querySelectorAll('tr').forEach(fillRooms); total();
    }
    arrival.addEventListener('change', () => { if (nights() <= 0) { const d = new Date(arrival.value); d.setDate(d.getDate() + 1); departure.value = d.toISOString().slice(0, 10); } refresh(); });
    departure.addEventListener('change', refresh);
    document.getElementById('addRoom').addEventListener('click', addRow);
    addRow(); refresh();

    const search = document.getElementById('guestSearch'), results = document.getElementById('guestResults');
    let timer;
    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            results.innerHTML = '';
            if (search.value.trim().length < 2) return;
            const res = await fetch(@json(route('guests.search')) + '?q=' + encodeURIComponent(search.value), { headers: { Accept: 'application/json' } });
            (await res.json()).forEach(g => {
                const a = document.createElement('button');
                a.type = 'button'; a.className = 'list-group-item list-group-item-action' + (g.is_blacklisted ? ' text-danger' : '');
                a.textContent = g.full_name + (g.phone ? ' · ' + g.phone : '');
                a.addEventListener('click', () => {
                    document.getElementById('guestId').value = g.id;
                    document.getElementById('guestPickedName').textContent = a.textContent;
                    document.getElementById('guestPicked').classList.remove('d-none');
                    document.getElementById('guestNew').classList.add('d-none');
                    results.innerHTML = '';
                });
                results.appendChild(a);
            });
        }, 250);
    });
    document.getElementById('guestClear').addEventListener('click', () => {
        document.getElementById('guestId').value = '';
        document.getElementById('guestPicked').classList.add('d-none');
        document.getElementById('guestNew').classList.remove('d-none');
    });
})();
</script>
</x-slot:scripts>
</x-layouts.app>
