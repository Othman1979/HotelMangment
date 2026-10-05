<x-layouts.app :title="__('Shifts')">
<h2 class="mb-3">{{ __('Shifts') }}</h2>
@if ($open)
    <div class="card card-body mb-3 d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
        <span>{{ __('Open shift') }} <strong>{{ $open->shift_no }}</strong> · {{ __('since') }} {{ $open->opened_at->format('Y-m-d H:i') }} · {{ __('Opening balance') }} <x-money :value="$open->opening_balance" /></span>
        <a class="btn btn-primary" href="{{ route('shifts.show', $open) }}">{{ __('Shift report / close') }}</a>
    </div>
@else
    <form method="post" action="{{ route('shifts.open') }}" class="card card-body mb-3 d-flex flex-row flex-wrap align-items-end gap-2">
        @csrf
        <div><label class="form-label">{{ __('Opening balance (cash in drawer)') }}</label><input type="number" step="0.001" min="0" name="opening_balance" value="0" class="form-control" required></div>
        <button class="btn btn-primary">{{ __('Open shift') }}</button>
    </form>
@endif
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>{{ __('Shift') }}</th><th>{{ __('User') }}</th><th>{{ __('Business date') }}</th><th>{{ __('Opened') }}</th><th>{{ __('Closed') }}</th><th class="text-end">{{ __('Expected cash') }}</th><th class="text-end">{{ __('Counted cash') }}</th><th class="text-end">{{ __('Difference') }}</th></tr></thead>
        <tbody>
        @forelse ($shifts as $s)
            <tr>
                <td><a href="{{ route('shifts.show', $s) }}">{{ $s->shift_no }}</a> @if ($s->isOpen())<span class="badge text-bg-success">{{ __('Open') }}</span>@endif</td>
                <td>{{ $s->user->name }}</td><td dir="ltr">{{ $s->business_date->toDateString() }}</td>
                <td>{{ $s->opened_at->format('Y-m-d H:i') }}</td><td>{{ $s->closed_at?->format('Y-m-d H:i') }}</td>
                <td class="text-end">@if ($s->expected_cash !== null)<x-money :value="$s->expected_cash" />@endif</td>
                <td class="text-end">@if ($s->counted_cash !== null)<x-money :value="$s->counted_cash" />@endif</td>
                <td class="text-end">@if ($s->difference !== null)<x-money :value="$s->difference" class="{{ (float) $s->difference != 0 ? 'text-danger fw-semibold' : '' }}" />@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $shifts->links() }}</div>
</x-layouts.app>
