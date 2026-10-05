<x-layouts.app :title="__($r['title'])">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">{{ __($r['title']) }}</h2>
    <a class="btn btn-primary" href="{{ route('setup.create', $r['key']) }}">+ {{ __('Add') }}</a>
</div>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr>@foreach ($r['list'] as $col)<th>{{ __($r['fields'][$col]['label'] ?? ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::snake($col)))) }}</th>@endforeach<th></th></tr></thead>
        <tbody>
        @forelse ($records as $rec)
            <tr>
                @foreach ($r['list'] as $col)
                    @php $v = $col === 'status' ? null : $rec->{$col}; @endphp
                    <td>
                        @if ($col === 'status')<x-room-code :room="$rec" />
                        @elseif (is_bool($v)){!! $v ? '<span class="text-success">✓</span>' : '<span class="text-muted">—</span>' !!}
                        @elseif ($v instanceof \Illuminate\Database\Eloquent\Model){{ method_exists($v, 'name') ? $v->name() : ($v->code ?? $v->id) }}
                        @elseif ($v instanceof \BackedEnum){{ method_exists($v, 'label') ? $v->label() : $v->value }}
                        @else{{ $v }}@endif
                    </td>
                @endforeach
                <td class="text-end"><a class="btn btn-sm btn-light" href="{{ route('setup.edit', [$r['key'], $rec->id]) }}">{{ __('Edit') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="{{ count($r['list']) + 1 }}" class="text-muted text-center">{{ __('No records.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $records->links() }}</div>
</x-layouts.app>
