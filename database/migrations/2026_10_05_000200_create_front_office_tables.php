<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('gender', 10)->nullable();
            $table->string('nationality', 60)->nullable();
            $table->string('id_type', 20)->nullable();
            $table->string('id_number', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_blacklisted')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index('full_name');
            $table->index('phone');
            $table->index(['id_type', 'id_number']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no', 20)->unique();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('phone');
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->string('status', 20)->default('confirmed');
            $table->string('external_ref', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'arrival_date']);
        });

        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('nightly_rate', 12, 3);
            $table->string('status', 20)->default('confirmed');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'arrival_date', 'departure_date']);
            $table->index(['status', 'arrival_date']);
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_no', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->timestamp('opened_at');
            $table->decimal('opening_balance', 12, 3)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('expected_cash', 12, 3)->nullable();
            $table->decimal('counted_cash', 12, 3)->nullable();
            $table->decimal('difference', 12, 3)->nullable();
            $table->string('status', 10)->default('open');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('night_audits', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('rooms_total')->default(0);
            $table->unsignedInteger('rooms_occupied')->default(0);
            $table->unsignedInteger('rooms_out_of_order')->default(0);
            $table->decimal('room_revenue', 14, 3)->default(0);
            $table->decimal('other_revenue', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('payments_total', 14, 3)->default(0);
            $table->unsignedInteger('no_shows')->default(0);
            $table->json('log')->nullable();
            $table->timestamps();
        });

        Schema::create('guest_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_no', 20)->unique();
            $table->string('type', 20)->default('guest');
            $table->foreignId('reservation_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('status', 10)->default('open');
            $table->decimal('balance', 14, 3)->default(0);
            $table->boolean('allow_posting')->default(true);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 20)->unique();
            $table->foreignId('guest_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 3);
            $table->string('reference', 60)->nullable();
            $table->boolean('is_deposit')->default(false);
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['business_date', 'payment_method_id']);
        });

        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no', 20)->unique();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->string('received_from', 150);
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();
        });

        Schema::create('outlet_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_no', 20)->unique();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->string('settlement', 10);
            $table->foreignId('guest_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->decimal('subtotal', 12, 3);
            $table->decimal('service_amount', 12, 3)->default(0);
            $table->decimal('tax_amount', 12, 3)->default(0);
            $table->decimal('total', 12, 3);
            $table->date('business_date');
            $table->foreignId('shift_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['outlet_id', 'business_date']);
        });

        Schema::create('outlet_check_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->decimal('quantity', 8, 2);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('amount', 12, 3);
        });

        Schema::create('guest_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('transaction_code_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 3)->default(0);
            $table->decimal('amount', 12, 3);
            $table->decimal('service_amount', 12, 3)->default(0);
            $table->decimal('tax_amount', 12, 3)->default(0);
            $table->foreignId('outlet_check_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('night_audit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('guest_transactions')->restrictOnDelete();
            $table->foreignId('transferred_from_account_id')->nullable()->constrained('guest_accounts')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['guest_account_id', 'business_date']);
            $table->index(['business_date', 'transaction_code_id']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 20)->unique();
            $table->foreignId('guest_account_id')->constrained()->restrictOnDelete();
            $table->string('bill_to', 150);
            $table->string('tax_number', 50)->nullable();
            $table->decimal('subtotal', 14, 3);
            $table->decimal('service_amount', 14, 3);
            $table->decimal('tax_amount', 14, 3);
            $table->decimal('total', 14, 3);
            $table->decimal('paid', 14, 3);
            $table->date('business_date');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['invoices', 'guest_transactions', 'outlet_check_lines', 'outlet_checks', 'receipt_vouchers', 'payments', 'guest_accounts', 'night_audits', 'shifts', 'reservation_rooms', 'reservations', 'guests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
