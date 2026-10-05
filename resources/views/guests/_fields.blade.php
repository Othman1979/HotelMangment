@php $p = $prefix ?? null; $n = fn ($f) => $p ? "{$p}[{$f}]" : $f; $o = fn ($f) => old($p ? "{$p}.{$f}" : $f, $guest?->{$f}); @endphp
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">{{ __('Full name') }} *</label><input name="{{ $n('full_name') }}" value="{{ $o('full_name') }}" class="form-control" maxlength="150" {{ $p ? '' : 'required' }}></div>
    <div class="col-md-3"><label class="form-label">{{ __('Phone') }}</label><input name="{{ $n('phone') }}" value="{{ $o('phone') }}" class="form-control" dir="ltr"></div>
    <div class="col-md-3"><label class="form-label">{{ __('Nationality') }}</label><input name="{{ $n('nationality') }}" value="{{ $o('nationality') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">{{ __('ID type') }}</label>
        <select name="{{ $n('id_type') }}" class="form-select"><option value=""></option>
            @foreach (['national_id' => 'National ID', 'passport' => 'Passport', 'residency' => 'Residency', 'other' => 'Other'] as $v => $l)
                <option value="{{ $v }}" @selected($o('id_type') === $v)>{{ __($l) }}</option>
            @endforeach
        </select></div>
    <div class="col-md-3"><label class="form-label">{{ __('ID number') }}</label><input name="{{ $n('id_number') }}" value="{{ $o('id_number') }}" class="form-control"></div>
</div>
