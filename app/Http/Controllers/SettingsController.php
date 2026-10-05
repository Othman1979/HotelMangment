<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\HotelSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings', ['settings' => HotelSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:150'],
            'name_en' => ['required', 'string', 'max:150'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'service_percent' => ['required', 'numeric', 'min:0', 'max:50'],
        ]);
        $settings = HotelSetting::current();
        $old = $settings->only(array_keys($data));
        $settings->update($data);
        AuditTrail::record('settings.update', $settings, $old, $data);

        return back()->with('ok', 'Saved successfully.');
    }
}
