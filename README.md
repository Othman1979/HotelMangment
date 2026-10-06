# Hotel Management & Reservation System

نظام إدارة الفنادق والحجوزات — مرحلة الدراسة والتصميم.

| الملف | المحتوى |
|---|---|
| [docs/01-Study.md](docs/01-Study.md) | دراسة النظام: النطاق، المستخدمون، الوحدات، دورة العمل، قواعد العمل، التقارير، خيارات التقنية، المراحل |
| [docs/02-Database-Design.md](docs/02-Database-Design.md) | تصميم قاعدة البيانات: الجداول، الحقول، العلاقات، وربطها بدورة Reservation → Check-In → Guest Account → Night Audit → Checkout |
| [docs/03-Open-Questions.md](docs/03-Open-Questions.md) | أسئلة مفتوحة تحتاج قرارك قبل بدء البرمجة |

## Running the application

Requirements: PHP 8.3+, Composer, MySQL 8 / MariaDB 10.6+.

```bash
composer install
cp .env.example .env        # set DB_* for your MySQL server
php artisan key:generate
php artisan migrate --seed  # creates the schema and demo hotel data
php artisan serve           # http://localhost:8000
```

Demo users (password `Hotel@123`, admin `Admin@123`): `admin`, `manager`, `frontdesk`, `cashier`, `audit`, `hk`, `outlet`.

Tests: `php artisan test` (SQLite in memory). Code style: `vendor/bin/pint`.

### Screens

| Area | Path |
|---|---|
| Dashboard, room rack | `/`, `/rack` |
| Reservations, walk-in, availability | `/reservations`, `/reservations/create?walk_in=1` |
| Arrivals / in-house / departures | `/front/arrivals`, `/front/in-house`, `/front/departures` |
| Guest accounts (folio, charges, payments, reversals, check-out) | `/accounts` |
| Cashier shifts | `/shifts` |
| Internal outlets POS (restaurant, cafe, room service, minibar, laundry) | `/pos` |
| Housekeeping and room blocks | `/housekeeping` |
| Night audit | `/night-audit` |
| Reports (CSV export) | `/reports` |
| Setup: room types, rooms, codes, payment methods, outlets, items, companies, users | `/setup/{resource}` |
