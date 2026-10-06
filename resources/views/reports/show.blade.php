@php $titles = ['revenue' => 'Revenue by code', 'payments' => 'Payments', 'occupancy' => 'Occupancy & ADR', 'balances' => 'Open balances', 'outlets' => 'Outlet checks', 'in-house' => 'In-house guests']; @endphp
<x-layouts.app :title="__($titles[$report])">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __($titles[$report]) }}</h2>
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        @unless (in_array($report, ['balances', 'in-house'], true))
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
            <button class="btn btn-light">{{ __('Show') }}</button>
        @endunless
        <button class="btn btn-light" name="export" value="csv">CSV</button>
        <button type="button" class="btn btn-light" onclick="window.print()">{{ __('Print') }}</button>
    </form>
</div>
<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead><tr>@foreach ($columns as $c)<th>{{ __($c) }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>@foreach (array_values($row) as $i => $v)<td>@if (is_numeric($v) && array_key_exists($i, $totals))<x-money :value="$v" />@else{{ $v }}@endif</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($columns) }}" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
        @if ($rows->isNotEmpty() && $totals)
            <tfoot><tr class="fw-semibold">@foreach ($columns as $i => $c)<td>@if (array_key_exists($i, $totals))<x-money :value="$totals[$i]" />@elseif ($i === 0){{ __('Total') }}@endif</td>@endforeach</tr></tfoot>
        @endif
    </table>
</div>
</x-layouts.app>
