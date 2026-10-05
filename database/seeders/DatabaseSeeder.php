<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\HotelSetting;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TransactionCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        HotelSetting::create([
            'name_ar' => 'فندق النخبة', 'name_en' => 'Elite Hotel', 'currency' => 'JOD',
            'business_date' => now()->toDateString(), 'tax_percent' => 16, 'service_percent' => 10,
            'address' => 'Amman, Jordan', 'phone' => '+962 6 000 0000',
        ]);

        foreach ([
            ['admin', 'مدير النظام', Role::Admin], ['manager', 'مدير الفندق', Role::Manager],
            ['frontdesk', 'موظف الاستقبال', Role::FrontDesk], ['cashier', 'أمين الصندوق', Role::Cashier],
            ['audit', 'المدقق الليلي', Role::NightAuditor], ['hk', 'مشرف التدبير', Role::Housekeeping],
            ['outlet', 'موظف المطعم', Role::Outlet],
        ] as [$username, $name, $role]) {
            User::create(['username' => $username, 'name' => $name, 'role' => $role, 'is_active' => true, 'password' => $username === 'admin' ? 'Admin@123' : 'Hotel@123']);
        }

        $codes = [];
        foreach ([
            ['ROOM', 'إقامة', 'Room charge', 'charge', 'rooms', true, false, false],
            ['FNB', 'طعام ومشروبات', 'Food & beverage', 'charge', 'fnb', true, true, true],
            ['MINI', 'ميني بار', 'Minibar', 'charge', 'fnb', true, false, true],
            ['LAUN', 'مغسلة', 'Laundry', 'charge', 'other', true, false, true],
            ['SPA', 'سبا', 'Spa', 'charge', 'other', true, true, true],
            ['MISC', 'متفرقات', 'Miscellaneous', 'charge', 'other', true, false, true],
            ['ADJ', 'تسوية', 'Adjustment', 'adjustment', 'other', false, false, true],
            ['NOSHOW', 'رسوم عدم حضور', 'No-show fee', 'charge', 'rooms', true, false, true],
            ['TRANS', 'تحويل رصيد', 'Balance transfer', 'adjustment', null, false, false, false],
            ['PCASH', 'دفع نقدي', 'Cash payment', 'payment', 'payment', false, false, false],
            ['PCARD', 'دفع بطاقة', 'Card payment', 'payment', 'payment', false, false, false],
            ['PBANK', 'تحويل بنكي', 'Bank transfer', 'payment', 'payment', false, false, false],
        ] as [$code, $ar, $en, $type, $group, $tax, $svc, $manual]) {
            $codes[$code] = TransactionCode::create(['code' => $code, 'name_ar' => $ar, 'name_en' => $en, 'type' => $type, 'revenue_group' => $group, 'is_taxable' => $tax, 'has_service' => $svc, 'is_manual' => $manual, 'is_active' => true]);
        }

        PaymentMethod::create(['code' => 'CASH', 'name_ar' => 'نقدي', 'name_en' => 'Cash', 'transaction_code_id' => $codes['PCASH']->id, 'is_cash' => true]);
        PaymentMethod::create(['code' => 'VISA', 'name_ar' => 'بطاقة ائتمان', 'name_en' => 'Credit card', 'transaction_code_id' => $codes['PCARD']->id, 'requires_reference' => true]);
        PaymentMethod::create(['code' => 'BANK', 'name_ar' => 'تحويل بنكي', 'name_en' => 'Bank transfer', 'transaction_code_id' => $codes['PBANK']->id, 'requires_reference' => true]);

        $types = [];
        foreach ([['STD', 'غرفة مفردة', 'Standard single', 1, 0, 45], ['DBL', 'غرفة مزدوجة', 'Standard double', 2, 1, 60], ['STE', 'جناح', 'Suite', 4, 2, 120]] as $i => [$code, $ar, $en, $adults, $children, $rate]) {
            $types[$code] = RoomType::create(['code' => $code, 'name_ar' => $ar, 'name_en' => $en, 'max_adults' => $adults, 'max_children' => $children, 'base_rate' => $rate, 'sort_order' => $i]);
        }
        foreach ([1 => ['STD', 6, 'DBL', 4], 2 => ['STD', 4, 'DBL', 6], 3 => ['DBL', 2, 'STE', 3]] as $floor => [$t1, $n1, $t2, $n2]) {
            $n = 1;
            foreach ([[$t1, $n1], [$t2, $n2]] as [$type, $count]) {
                for ($i = 0; $i < $count; $i++, $n++) {
                    Room::create(['room_number' => $floor.str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'room_type_id' => $types[$type]->id, 'floor' => (string) $floor]);
                }
            }
        }

        $menus = [
            ['REST', 'المطعم', 'Restaurant', 'FNB', [
                ['Main', 'منسف', 'Mansaf', 12], ['Main', 'مشاوي مشكلة', 'Mixed grill', 10], ['Main', 'دجاج مشوي', 'Grilled chicken', 7.5],
                ['Starters', 'حمص', 'Hummus', 2], ['Starters', 'سلطة فتوش', 'Fattoush salad', 2.5], ['Drinks', 'عصير ليمون', 'Lemon mint', 2], ['Drinks', 'مياه معدنية', 'Mineral water', 0.75],
            ]],
            ['CAFE', 'الكافيه', 'Cafe', 'FNB', [['Hot', 'قهوة عربية', 'Arabic coffee', 1.5], ['Hot', 'كابتشينو', 'Cappuccino', 2.75], ['Hot', 'شاي', 'Tea', 1], ['Sweets', 'كنافة', 'Knafeh', 3]]],
            ['RS', 'خدمة الغرف', 'Room service', 'FNB', [['Breakfast', 'فطور عربي', 'Arabic breakfast', 6], ['Breakfast', 'فطور كونتيننتال', 'Continental breakfast', 7], ['Snacks', 'ساندويش كلوب', 'Club sandwich', 5]]],
            ['MINI', 'الميني بار', 'Minibar', 'MINI', [['Drinks', 'مشروب غازي', 'Soft drink', 1.5], ['Drinks', 'مياه', 'Water', 0.75], ['Snacks', 'شيبس', 'Chips', 1.25], ['Snacks', 'شوكولاتة', 'Chocolate', 1.75]]],
            ['LAUN', 'المغسلة', 'Laundry', 'LAUN', [['Wash', 'قميص', 'Shirt', 1.5], ['Wash', 'بنطال', 'Trousers', 2], ['Wash', 'بدلة', 'Suit', 5], ['Press', 'كي قميص', 'Shirt pressing', 0.75]]],
        ];
        foreach ($menus as [$code, $ar, $en, $txn, $items]) {
            $outlet = Outlet::create(['code' => $code, 'name_ar' => $ar, 'name_en' => $en, 'transaction_code_id' => $codes[$txn]->id]);
            foreach ($items as [$cat, $iar, $ien, $price]) {
                $outlet->items()->create(['category' => $cat, 'name_ar' => $iar, 'name_en' => $ien, 'price' => $price]);
            }
        }

        Company::create(['name' => 'شركة الأفق للتجارة', 'tax_number' => '123456789', 'contact_person' => 'أحمد', 'phone' => '0790000000', 'credit_limit' => 2000]);
    }
}
