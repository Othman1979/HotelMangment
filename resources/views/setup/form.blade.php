<x-layouts.app :title="__($r['title'])">
<h2 class="mb-3">{{ __($r['title']) }} · {{ $record->exists ? __('Edit') : __('Add') }}</h2>
<form method="post" action="{{ $record->exists ? route('setup.update', [$r['key'], $record->id]) : route('setup.store', $r['key']) }}" class="card card-body">
    @csrf
    @if ($record->exists) @method('PUT') @endif
    <div class="row g-3">
        @foreach ($r['fields'] as $name => $f)
            @php $val = old($name, $f['type'] === 'password' ? '' : ($record->{$name} instanceof \BackedEnum ? $record->{$name}->value : $record->{$name})); @endphp
            @if ($f['type'] === 'checkbox')
                <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="{{ $name }}" value="1" id="f_{{ $name }}" class="form-check-input" @checked($val)><label for="f_{{ $name }}" class="form-check-label">{{ __($f['label']) }}</label></div></div>
            @elseif ($f['type'] === 'select')
                <div class="col-md-6"><label class="form-label">{{ __($f['label']) }}</label>
                    <select name="{{ $name }}" class="form-select"><option value=""></option>
                        @foreach (($f['options'])() as $k => $l)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $l }}</option>@endforeach
                    </select></div>
            @else
                <div class="col-md-6"><label class="form-label">{{ __($f['label']) }}</label>
                    <input type="{{ $f['type'] }}" name="{{ $name }}" value="{{ $val }}" class="form-control" @isset($f['step']) step="{{ $f['step'] }}" @endisset @if ($f['type'] === 'password') autocomplete="new-password" placeholder="{{ $record->exists ? __('Leave empty to keep the current password') : '' }}" @endif></div>
            @endif
        @endforeach
    </div>
    <div class="mt-3"><button class="btn btn-primary">{{ __('Save') }}</button> <a class="btn btn-light" href="{{ route('setup.index', $r['key']) }}">{{ __('Cancel') }}</a></div>
</form>
</x-layouts.app>
