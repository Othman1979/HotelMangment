<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->string('tax_number', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->char('currency', 3)->default('JOD');
            $table->date('business_date');
            $table->time('check_in_time')->default('14:00');
            $table->time('check_out_time')->default('12:00');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('service_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name', 30)->primary();
            $table->string('prefix', 10);
            $table->unsignedBigInteger('next_value')->default(1);
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->unsignedTinyInteger('max_adults')->default(2);
            $table->unsignedTinyInteger('max_children')->default(0);
            $table->decimal('base_rate', 12, 3);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 10)->unique();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('floor', 20)->nullable();
            $table->string('housekeeping_status', 20)->default('clean');
            $table->string('occupancy_status', 20)->default('vacant');
            $table->string('service_status', 20)->default('in_service');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('type', 20);
            $table->string('reason');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'from_date', 'to_date']);
        });

        Schema::create('transaction_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('type', 20);
            $table->string('revenue_group', 20)->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('has_service')->default(false);
            $table->boolean('is_manual')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->foreignId('transaction_code_id')->constrained()->restrictOnDelete();
            $table->boolean('is_cash')->default(false);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->foreignId('transaction_code_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('outlet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50)->nullable();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->decimal('price', 12, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('tax_number', 50)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->decimal('credit_limit', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_trails', 'companies', 'outlet_items', 'outlets', 'payment_methods', 'transaction_codes', 'room_blocks', 'rooms', 'room_types', 'sequences', 'hotel_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
