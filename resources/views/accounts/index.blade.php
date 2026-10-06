<x-layouts.app :title="__('Accounts')">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Accounts') }}</h2>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Account no. or name') }}"></div>
    <div class="col-md-3"><select name="type" class="form-select"><option value="">{{ __('All types') }}</option>
        @foreach (\App\Enums\AccountType::cases() as $t)<option value="{{ $t->value }}" @selected($type === $t->value)>{{ $t->label() }}</option>@endforeach
    </select></div>
    <div class="col-md-3"><select name="status" class="form-select">
        <option value="open">{{ __('Open') }}</option><option value="closed" @selected(request('status') === 'closed')>{{ __('Closed') }}</option>
    </select></div>
    <div class="col-md-2"><button class="btn btn-light w-100">{{ __('Search') }}</button></div>
</form>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th><th>{{ __('Name') }}</th><th>{{ __('Room') }}</th><th>{{ __('Balance') }}</th></tr></thead>
        <tbody>
        @forelse ($accounts as $a)
            <tr><td><a href="{{ route('accounts.show', $a) }}">{{ $a->account_no }}</a></td><td>{{ $a->type->label() }}</td><td>{{ $a->name }}</td><td>{{ $a->stay?->room?->room_number }}</td><td><x-money :value="$a->balance" :sign="true" /></td></tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>
</x-layouts.app>
