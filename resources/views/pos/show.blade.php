<x-layouts.app :title="$outlet->name()">
@php $code = $outlet->transactionCode; @endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ $outlet->name() }}</h2>
    <a class="btn btn-light" href="{{ route('pos.index') }}">{{ __('All outlets') }}</a>
</div>
<form method="post" action="{{ route('pos.store', $outlet) }}" id="posForm">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <input id="itemFilter" class="form-control mb-3" placeholder="{{ __('Search items') }}">
            @forelse ($items as $category => $group)
                <h6 class="text-muted mt-3 item-cat">{{ $category }}</h6>
                <div class="row g-2">
                    @foreach ($group as $item)
                        <div class="col-6 col-md-4 col-xxl-3 item-col" data-name="{{ mb_strtolower($item->name_ar.' '.$item->name_en) }}">
                            <button type="button" class="pos-item" data-id="{{ $item->id }}" data-name="{{ $item->name() }}" data-price="{{ $item->price }}">
                                <span class="d-block fw-semibold">{{ $item->name() }}</span>
                                <span class="price" dir="ltr">{{ number_format((float) $item->price, 3) }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-muted">{{ __('This outlet has no items yet.') }}</p>
            @endforelse
        </div>
        <div class="col-lg-4">
            <div class="card pos-cart">
                <div class="card-header">{{ __('Check') }}</div>
                <ul class="list-group list-group-flush" id="cart"><li class="list-group-item text-muted small" id="cartEmpty">{{ __('Tap items to add them.') }}</li></ul>
                <div class="card-body border-top">
                    <div class="d-flex justify-content-between small"><span>{{ __('Subtotal') }}</span><span id="sub" dir="ltr">0.000</span></div>
                    <div class="d-flex justify-content-between small"><span>{{ __('Service') }} {{ $code->has_service ? (float) $hotel->service_percent.'%' : '' }}</span><span id="svc" dir="ltr">0.000</span></div>
                    <div class="d-flex justify-content-between small"><span>{{ __('Tax') }} {{ $code->is_taxable ? (float) $hotel->tax_percent.'%' : '' }}</span><span id="tax" dir="ltr">0.000</span></div>
                    <div class="d-flex justify-content-between fs-5 fw-semibold mt-1"><span>{{ __('Total') }}</span><span id="tot" dir="ltr">0.000</span></div>
                    <hr>
                    <div class="btn-group w-100 mb-2">
                        <input type="radio" class="btn-check" name="settlement" value="room" id="sRoom" checked>
                        <label class="btn btn-outline-primary btn-sm" for="sRoom">{{ __('Charge to room') }}</label>
                        <input type="radio" class="btn-check" name="settlement" value="direct" id="sDirect">
                        <label class="btn btn-outline-primary btn-sm" for="sDirect">{{ __('Pay now') }}</label>
                    </div>
                    <div id="roomBox">
                        <select name="guest_account_id" class="form-select">
                            <option value="">{{ __('Choose room') }}</option>
                            @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->stay->room?->room_number }} · {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div id="directBox" class="d-none">
                        @unless ($shift)<div class="alert alert-warning small py-2">{{ __('Open a cashier shift first.') }} <a href="{{ route('shifts.index') }}">{{ __('Open shift') }}</a></div>@endunless
                        <select name="payment_method_id" class="form-select mb-2">@foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name() }}</option>@endforeach</select>
                        <input name="customer_name" class="form-control" placeholder="{{ __('Customer name (optional)') }}" maxlength="150">
                    </div>
                    <div id="qtyInputs"></div>
                </div>
                <div class="card-footer"><button class="btn btn-primary w-100" id="saveBtn" disabled>{{ __('Save check') }}</button></div>
            </div>
        </div>
    </div>
</form>
<x-slot:scripts>
<script>
(function () {
    const svcPct = {{ $code->has_service ? (float) $hotel->service_percent : 0 }}, taxPct = {{ $code->is_taxable ? (float) $hotel->tax_percent : 0 }};
    const cart = new Map(), list = document.getElementById('cart'), inputs = document.getElementById('qtyInputs');
    const r3 = n => Math.round(n * 1000) / 1000, f = n => r3(n).toFixed(3);
    function render() {
        list.querySelectorAll('.cart-line').forEach(e => e.remove());
        inputs.innerHTML = '';
        let sub = 0;
        cart.forEach((l, id) => {
            sub += r3(l.price * l.qty);
            const li = document.createElement('li');
            li.className = 'list-group-item cart-line d-flex justify-content-between align-items-center gap-2';
            li.innerHTML = '<span class="flex-fill"></span><span class="btn-group btn-group-sm"><button type="button" class="btn btn-light">−</button><span class="btn btn-light disabled"></span><button type="button" class="btn btn-light">+</button></span><span dir="ltr" class="font-monospace"></span>';
            li.children[0].textContent = l.name;
            li.querySelector('.disabled').textContent = l.qty;
            li.children[2].textContent = f(l.price * l.qty);
            const [minus, , plus] = li.querySelectorAll('.btn');
            minus.onclick = () => { l.qty--; if (l.qty <= 0) cart.delete(id); render(); };
            plus.onclick = () => { l.qty++; render(); };
            list.appendChild(li);
            inputs.insertAdjacentHTML('beforeend', '<input type="hidden" name="qty[' + id + ']" value="' + l.qty + '">');
        });
        const svc = r3(sub * svcPct / 100), tax = r3((sub + svc) * taxPct / 100);
        document.getElementById('sub').textContent = f(sub);
        document.getElementById('svc').textContent = f(svc);
        document.getElementById('tax').textContent = f(tax);
        document.getElementById('tot').textContent = f(sub + svc + tax);
        document.getElementById('cartEmpty').classList.toggle('d-none', cart.size > 0);
        document.getElementById('saveBtn').disabled = cart.size === 0;
    }
    document.querySelectorAll('.pos-item').forEach(b => b.addEventListener('click', () => {
        const id = b.dataset.id, l = cart.get(id) || { name: b.dataset.name, price: parseFloat(b.dataset.price), qty: 0 };
        l.qty++; cart.set(id, l); render();
    }));
    document.querySelectorAll('[name=settlement]').forEach(r => r.addEventListener('change', () => {
        const room = document.getElementById('sRoom').checked;
        document.getElementById('roomBox').classList.toggle('d-none', !room);
        document.getElementById('directBox').classList.toggle('d-none', room);
    }));
    document.getElementById('itemFilter').addEventListener('input', e => {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('.item-col').forEach(c => c.classList.toggle('d-none', q && !c.dataset.name.includes(q)));
    });
})();
</script>
</x-slot:scripts>
</x-layouts.app>
