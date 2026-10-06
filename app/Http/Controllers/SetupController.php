<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\OutletItem;
use App\Models\PaymentMethod;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TransactionCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Generic list/create/edit screens for simple master-data tables. */
class SetupController extends Controller
{
    private function resources(): array
    {
        $codes = fn (?string $type = null) => TransactionCode::query()->when($type, fn ($q) => $q->where('type', $type))->orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->code.' - '.$c->name()])->all();
        $bilingual = [
            'name_ar' => ['label' => 'Arabic name', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            'name_en' => ['label' => 'English name', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
        ];
        $active = ['is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => []]];

        return [
            'room-types' => ['title' => 'Room types', 'model' => RoomType::class, 'order' => 'sort_order', 'list' => ['code', 'name_ar', 'name_en', 'base_rate', 'max_adults', 'is_active'],
                'fields' => ['code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:10', 'unique']]] + $bilingual + [
                    'base_rate' => ['label' => 'Base rate', 'type' => 'number', 'step' => '0.001', 'rules' => ['required', 'numeric', 'min:0']],
                    'max_adults' => ['label' => 'Max adults', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:10']],
                    'max_children' => ['label' => 'Max children', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:10']],
                    'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ] + $active],
            'rooms' => ['title' => 'Rooms', 'model' => Room::class, 'order' => 'room_number', 'with' => ['roomType'], 'list' => ['room_number', 'roomType', 'floor', 'status', 'is_active'],
                'fields' => [
                    'room_number' => ['label' => 'Room number', 'type' => 'text', 'rules' => ['required', 'string', 'max:10', 'unique']],
                    'room_type_id' => ['label' => 'Room type', 'type' => 'select', 'options' => fn () => RoomType::query()->orderBy('sort_order')->get()->mapWithKeys(fn ($t) => [$t->id => $t->name()])->all(), 'rules' => ['required', 'exists:room_types,id']],
                    'floor' => ['label' => 'Floor', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:20']],
                    'housekeeping_status' => ['label' => 'Housekeeping', 'type' => 'select', 'options' => fn () => collect(HousekeepingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(), 'rules' => ['required', Rule::enum(HousekeepingStatus::class)]],
                    'notes' => ['label' => 'Notes', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                ] + $active],
            'transaction-codes' => ['title' => 'Transaction codes', 'model' => TransactionCode::class, 'order' => 'code', 'list' => ['code', 'name_ar', 'name_en', 'type', 'revenue_group', 'is_taxable', 'has_service', 'is_manual', 'is_active'],
                'fields' => ['code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:10', 'unique']]] + $bilingual + [
                    'type' => ['label' => 'Type', 'type' => 'select', 'options' => fn () => collect(TransactionType::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(), 'rules' => ['required', Rule::enum(TransactionType::class)]],
                    'revenue_group' => ['label' => 'Revenue group', 'type' => 'select', 'options' => fn () => ['rooms' => __('Rooms'), 'fnb' => __('Food & beverage'), 'other' => __('Other'), 'payment' => __('Payment')], 'rules' => ['nullable', 'in:rooms,fnb,other,payment']],
                    'is_taxable' => ['label' => 'Taxable', 'type' => 'checkbox', 'rules' => []],
                    'has_service' => ['label' => 'Service charge', 'type' => 'checkbox', 'rules' => []],
                    'is_manual' => ['label' => 'Manual posting', 'type' => 'checkbox', 'rules' => []],
                ] + $active],
            'payment-methods' => ['title' => 'Payment methods', 'model' => PaymentMethod::class, 'order' => 'id', 'with' => ['transactionCode'], 'list' => ['code', 'name_ar', 'name_en', 'transactionCode', 'is_cash', 'requires_reference', 'is_active'],
                'fields' => ['code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:10', 'unique']]] + $bilingual + [
                    'transaction_code_id' => ['label' => 'Transaction code', 'type' => 'select', 'options' => fn () => $codes(TransactionType::Payment->value), 'rules' => ['required', Rule::exists('transaction_codes', 'id')->where('type', 'payment')]],
                    'is_cash' => ['label' => 'Cash (counted in drawer)', 'type' => 'checkbox', 'rules' => []],
                    'requires_reference' => ['label' => 'Requires reference', 'type' => 'checkbox', 'rules' => []],
                ] + $active],
            'outlets' => ['title' => 'Outlets', 'model' => Outlet::class, 'order' => 'id', 'with' => ['transactionCode'], 'list' => ['code', 'name_ar', 'name_en', 'transactionCode', 'is_active'],
                'fields' => ['code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:10', 'unique']]] + $bilingual + [
                    'transaction_code_id' => ['label' => 'Revenue code', 'type' => 'select', 'options' => fn () => $codes(TransactionType::Charge->value), 'rules' => ['required', Rule::exists('transaction_codes', 'id')->where('type', 'charge')]],
                ] + $active],
            'outlet-items' => ['title' => 'Outlet items', 'model' => OutletItem::class, 'order' => 'outlet_id', 'with' => ['outlet'], 'list' => ['outlet', 'category', 'name_ar', 'name_en', 'price', 'is_active'],
                'fields' => [
                    'outlet_id' => ['label' => 'Outlet', 'type' => 'select', 'options' => fn () => Outlet::query()->orderBy('id')->get()->mapWithKeys(fn ($o) => [$o->id => $o->name()])->all(), 'rules' => ['required', 'exists:outlets,id']],
                    'category' => ['label' => 'Category', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50']],
                ] + $bilingual + [
                    'price' => ['label' => 'Price', 'type' => 'number', 'step' => '0.001', 'rules' => ['required', 'numeric', 'min:0']],
                ] + $active],
            'companies' => ['title' => 'Companies', 'model' => Company::class, 'order' => 'name', 'list' => ['name', 'tax_number', 'contact_person', 'phone', 'credit_limit', 'is_active'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
                    'tax_number' => ['label' => 'Tax number', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50']],
                    'contact_person' => ['label' => 'Contact person', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
                    'phone' => ['label' => 'Phone', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50']],
                    'email' => ['label' => 'Email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:150']],
                    'credit_limit' => ['label' => 'Credit limit', 'type' => 'number', 'step' => '0.001', 'rules' => ['required', 'numeric', 'min:0']],
                ] + $active],
            'users' => ['title' => 'Users', 'model' => User::class, 'order' => 'username', 'list' => ['username', 'name', 'role', 'is_active'],
                'fields' => [
                    'username' => ['label' => 'Username', 'type' => 'text', 'rules' => ['required', 'string', 'max:50', 'alpha_dash', 'unique']],
                    'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
                    'email' => ['label' => 'Email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:150']],
                    'role' => ['label' => 'Role', 'type' => 'select', 'options' => fn () => collect(Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all(), 'rules' => ['required', Rule::enum(Role::class)]],
                    'password' => ['label' => 'Password', 'type' => 'password', 'rules' => ['nullable', 'required_on_create', 'string', 'min:8', 'max:100']],
                ] + $active],
        ];
    }

    private function resource(string $key): array
    {
        $resources = $this->resources();
        abort_unless(isset($resources[$key]), 404);

        return $resources[$key] + ['key' => $key, 'all' => $resources];
    }

    public function index(string $resource)
    {
        $r = $this->resource($resource);
        $records = $r['model']::query()->with($r['with'] ?? [])->orderBy($r['order'])->paginate(50);

        return view('setup.index', ['r' => $r, 'records' => $records]);
    }

    public function create(string $resource)
    {
        $r = $this->resource($resource);

        return view('setup.form', ['r' => $r, 'record' => new $r['model'](['is_active' => true])]);
    }

    public function store(Request $request, string $resource)
    {
        $r = $this->resource($resource);
        $record = $r['model']::create($this->validated($request, $r, null));
        AuditTrail::record($resource.'.create', $record);

        return redirect()->route('setup.index', $resource)->with('ok', 'Saved successfully.');
    }

    public function edit(string $resource, int $id)
    {
        $r = $this->resource($resource);

        return view('setup.form', ['r' => $r, 'record' => $r['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $r = $this->resource($resource);
        $record = $r['model']::findOrFail($id);
        $data = $this->validated($request, $r, $record);
        if ($record instanceof User && $record->is(auth()->user()) && (! $data['is_active'] || $data['role'] !== Role::Admin->value && $record->isAdmin())) {
            return back()->with('err', 'You cannot disable or demote your own account.');
        }
        $old = $record->only(array_keys($data));
        $record->update($data);
        AuditTrail::record($resource.'.update', $record, array_diff_key($old, ['password' => 1]), array_diff_key($data, ['password' => 1]));

        return redirect()->route('setup.index', $resource)->with('ok', 'Saved successfully.');
    }

    private function validated(Request $request, array $r, ?Model $record): array
    {
        $table = (new $r['model'])->getTable();
        $rules = [];
        foreach ($r['fields'] as $name => $field) {
            $rules[$name] = collect($field['rules'])->map(fn ($rule) => match ($rule) {
                'unique' => Rule::unique($table, $name)->ignore($record?->id),
                'required_on_create' => $record ? 'nullable' : 'required',
                default => $rule,
            })->all();
        }
        $data = $request->validate($rules);
        foreach ($r['fields'] as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
