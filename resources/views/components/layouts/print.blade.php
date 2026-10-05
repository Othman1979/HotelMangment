<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <x-head :title="$title ?? null" />
</head>
<body class="print-body">
    <div class="print-actions d-print-none">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">{{ __('Print') }}</button>
        <button type="button" class="btn btn-light btn-sm" onclick="history.length > 1 ? history.back() : window.close()">{{ __('Back') }}</button>
    </div>
    <div class="print-sheet">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-3">
            <div>
                <h4 class="mb-0">{{ $hotel->name() }}</h4>
                <div class="small text-muted">{{ $hotel->address }} {{ $hotel->phone }}</div>
                @if ($hotel->tax_number)<div class="small">{{ __('Tax number') }}: {{ $hotel->tax_number }}</div>@endif
            </div>
            <h5 class="mb-0">{{ $title }}</h5>
        </div>
        {{ $slot }}
    </div>
</body>
</html>
