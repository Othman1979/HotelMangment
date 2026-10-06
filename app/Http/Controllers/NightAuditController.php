<?php

namespace App\Http\Controllers;

use App\Models\HotelSetting;
use App\Models\NightAudit;
use App\Services\NightAuditor;
use Illuminate\Http\Request;

class NightAuditController extends Controller
{
    public function index(NightAuditor $auditor)
    {
        $date = HotelSetting::businessDate();

        return view('night-audit.index', [
            'date' => $date,
            'checks' => $auditor->checks($date),
            'audits' => NightAudit::query()->with('user')->latest('business_date')->limit(30)->get(),
        ]);
    }

    public function run(Request $request, NightAuditor $auditor)
    {
        $request->validate(['confirm' => ['accepted']]);
        $audit = $auditor->run($request->user());

        return redirect()->route('night-audit.index')->with('ok', 'Night audit for :date completed.')->with('ok_replace', ['date' => $audit->business_date->toDateString()]);
    }
}
