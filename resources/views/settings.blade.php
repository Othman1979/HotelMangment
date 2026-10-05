<x-layouts.app :title="__('Hotel settings')">
<h2 class="mb-3">{{ __('Hotel settings') }}</h2>
<form method="post" action="{{ route('settings.update') }}" class="card card-body">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">{{ __('Arabic name') }}</label><input name="name_ar" value="{{ old('name_ar', $settings->name_ar) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('English name') }}</label><input name="name_en" value="{{ old('name_en', $settings->name_en) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Tax number') }}</label><input name="tax_number" value="{{ old('tax_number', $settings->tax_number) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $settings->phone) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Currency') }}</label><input name="currency" value="{{ old('currency', $settings->currency) }}" class="form-control" maxlength="3" required></div>
        <div class="col-12"><label class="form-label">{{ __('Address') }}</label><input name="address" value="{{ old('address', $settings->address) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Check-in time') }}</label><input type="time" name="check_in_time" value="{{ old('check_in_time', substr($settings->check_in_time, 0, 5)) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Check-out time') }}</label><input type="time" name="check_out_time" value="{{ old('check_out_time', substr($settings->check_out_time, 0, 5)) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Tax %') }}</label><input type="number" step="0.01" name="tax_percent" value="{{ old('tax_percent', $settings->tax_percent) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Service %') }}</label><input type="number" step="0.01" name="service_percent" value="{{ old('service_percent', $settings->service_percent) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Business date') }}</label><input value="{{ $settings->business_date->toDateString() }}" class="form-control" readonly></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">{{ __('Save') }}</button></div>
</form>
</x-layouts.app>
