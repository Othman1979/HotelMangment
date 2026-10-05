<x-layouts.app :title="$guest->exists ? __('Edit guest') : __('New guest')">
<h2 class="mb-3">{{ $guest->exists ? __('Edit guest') : __('New guest') }}</h2>
<form method="post" action="{{ $guest->exists ? route('guests.update', $guest) : route('guests.store') }}" class="card card-body">
    @csrf
    @if ($guest->exists) @method('PUT') @endif
    @include('guests._fields', ['guest' => $guest])
    <div class="row g-3 mt-0">
        <div class="col-md-3"><label class="form-label">{{ __('Gender') }}</label>
            <select name="gender" class="form-select"><option value=""></option>
                <option value="male" @selected(old('gender', $guest->gender) === 'male')>{{ __('Male') }}</option>
                <option value="female" @selected(old('gender', $guest->gender) === 'female')>{{ __('Female') }}</option>
            </select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Date of birth') }}</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $guest->date_of_birth?->toDateString()) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email', $guest->email) }}" class="form-control" dir="ltr"></div>
        <div class="col-md-6"><label class="form-label">{{ __('Address') }}</label><input name="address" value="{{ old('address', $guest->address) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">{{ __('Notes') }}</label><input name="notes" value="{{ old('notes', $guest->notes) }}" class="form-control"></div>
        <div class="col-12 d-flex gap-4">
            <div class="form-check"><input type="checkbox" name="is_vip" value="1" id="vip" class="form-check-input" @checked(old('is_vip', $guest->is_vip))><label for="vip" class="form-check-label">VIP</label></div>
            <div class="form-check"><input type="checkbox" name="is_blacklisted" value="1" id="bl" class="form-check-input" @checked(old('is_blacklisted', $guest->is_blacklisted))><label for="bl" class="form-check-label">{{ __('Blacklisted') }}</label></div>
        </div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">{{ __('Save') }}</button> <a class="btn btn-light" href="{{ url()->previous() }}">{{ __('Cancel') }}</a></div>
</form>
</x-layouts.app>
