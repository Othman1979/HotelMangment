<x-layouts.app :title="__('Guests')">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __('Guests') }}</h2>
    <div class="d-flex gap-2">
        <form method="get"><input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Search name, phone or ID') }}"></form>
        <a class="btn btn-primary" href="{{ route('guests.create') }}">+ {{ __('New guest') }}</a>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Nationality') }}</th><th>{{ __('ID number') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($guests as $g)
            <tr>
                <td><a href="{{ route('guests.show', $g) }}">{{ $g->full_name }}</a>
                    @if ($g->is_vip)<span class="badge text-bg-warning">VIP</span>@endif
                    @if ($g->is_blacklisted)<span class="badge text-bg-danger">{{ __('Blacklisted') }}</span>@endif</td>
                <td dir="ltr">{{ $g->phone }}</td><td>{{ $g->nationality }}</td><td>{{ $g->id_number }}</td>
                <td class="text-end"><a class="btn btn-sm btn-light" href="{{ route('reservations.create', ['guest_id' => $g->id]) }}">{{ __('Book') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $guests->links() }}</div>
</x-layouts.app>
