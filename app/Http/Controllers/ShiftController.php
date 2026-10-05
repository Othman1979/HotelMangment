<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AuditTrail;
use App\Models\HotelSetting;
use App\Models\Sequence;
use App\Models\Shift;
use App\Services\HotelException;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $shifts = Shift::query()->with('user')
            ->when(! $user->hasRole(Role::Manager), fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')->paginate(30);

        return view('shifts.index', ['shifts' => $shifts, 'open' => $user->openShift()]);
    }

    public function open(Request $request)
    {
        $data = $request->validate(['opening_balance' => ['required', 'numeric', 'min:0', 'max:99999']]);
        $user = $request->user();
        if ($user->openShift()) {
            throw new HotelException('You already have an open shift.');
        }
        $shift = Shift::create([
            'shift_no' => Sequence::next('shift'),
            'user_id' => $user->id,
            'business_date' => HotelSetting::businessDate(),
            'opened_at' => now(),
            'opening_balance' => $data['opening_balance'],
            'status' => 'open',
        ]);
        AuditTrail::record('shift.open', $shift);

        return back()->with('ok', 'Shift opened.');
    }

    public function show(Shift $shift)
    {
        $this->authorizeShift($shift);
        $payments = $shift->payments()->with(['method', 'account', 'voucher'])->orderBy('id')->get();
        $byMethod = $payments->groupBy(fn ($p) => $p->method->name())->map->sum('amount');

        return view('shifts.show', compact('shift', 'payments', 'byMethod'));
    }

    public function close(Request $request, Shift $shift)
    {
        $this->authorizeShift($shift);
        $data = $request->validate(['counted_cash' => ['required', 'numeric', 'min:0', 'max:999999'], 'notes' => ['nullable', 'string', 'max:255']]);
        if (! $shift->isOpen()) {
            throw new HotelException('This shift is already closed.');
        }
        $expected = round((float) $shift->opening_balance + (float) $shift->cashCollected(), 3);
        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expected,
            'counted_cash' => $data['counted_cash'],
            'difference' => round((float) $data['counted_cash'] - $expected, 3),
            'status' => 'closed',
            'notes' => $data['notes'] ?? null,
        ]);
        AuditTrail::record('shift.close', $shift, null, ['expected' => $expected, 'counted' => $data['counted_cash']]);

        return redirect()->route('shifts.show', $shift)->with('ok', 'Shift closed.');
    }

    private function authorizeShift(Shift $shift): void
    {
        abort_unless($shift->user_id === auth()->id() || auth()->user()->hasRole(Role::Manager), 403);
    }
}
