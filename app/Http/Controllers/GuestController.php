<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $guests = Guest::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")->orWhere('id_number', 'like', "%{$q}%")))
            ->orderBy('full_name')->paginate(25)->withQueryString();

        return view('guests.index', compact('guests', 'q'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return Guest::query()->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")
            ->orderBy('full_name')->limit(10)->get(['id', 'full_name', 'phone', 'nationality', 'is_blacklisted']);
    }

    public function create()
    {
        return view('guests.form', ['guest' => new Guest]);
    }

    public function store(Request $request)
    {
        $guest = Guest::create($this->validated($request));
        AuditTrail::record('guest.create', $guest);

        return redirect()->route('guests.show', $guest)->with('ok', 'Saved successfully.');
    }

    public function show(Guest $guest)
    {
        $guest->load(['reservations' => fn ($q) => $q->latest('arrival_date')->limit(20)]);

        return view('guests.show', compact('guest'));
    }

    public function edit(Guest $guest)
    {
        return view('guests.form', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $old = $guest->only(['full_name', 'is_blacklisted', 'is_vip']);
        $guest->update($this->validated($request));
        AuditTrail::record('guest.update', $guest, $old, $guest->only(array_keys($old)));

        return redirect()->route('guests.show', $guest)->with('ok', 'Saved successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'id_type' => ['nullable', 'in:national_id,passport,residency,other'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        return $data + ['is_vip' => $request->boolean('is_vip'), 'is_blacklisted' => $request->boolean('is_blacklisted')];
    }
}
