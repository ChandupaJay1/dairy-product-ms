<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Operations, Returns, Crates, Settlements and Reports.
     */
    public function up(): void
    {
        // 1. Stock Returns & Expiry Management
        if (!Schema::hasTable('stock_returns')) {
            Schema::create('stock_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number', 50)->unique();
                $table->unsignedBigInteger('shop_id');
                $table->integer('dispatch_id')->nullable();
                $table->integer('sales_rep_id')->nullable();
                $table->string('flavor_name', 100);
                $table->string('batch_number', 50);
                $table->date('expiry_date')->nullable();
                $table->integer('quantity_cups');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total_value', 12, 2);
                $table->enum('return_reason', ['EXPIRED', 'LEAK_DAMAGED', 'SPOILED_TASTE', 'OVERSTOCK'])->default('EXPIRED');
                $table->enum('settlement_action', ['CREDIT_NOTE', 'REPLACEMENT', 'REFUND'])->default('CREDIT_NOTE');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. Crate & Container Tracking (Plastic Crates & Clay Treacle Pots)
        if (!Schema::hasTable('crate_records')) {
            Schema::create('crate_records', function (Blueprint $table) {
                $table->id();
                $table->integer('dispatch_id');
                $table->unsignedBigInteger('shop_id')->nullable();
                $table->integer('crates_issued')->default(0);
                $table->integer('crates_collected')->default(0);
                $table->integer('curd_pots_issued')->default(0);
                $table->integer('curd_pots_collected')->default(0);
                $table->decimal('pot_deposit_fee', 10, 2)->default(0.00);
                $table->string('notes', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 3. Day-End Daily Settlement & Cash/Stock Reconciliation
        if (!Schema::hasTable('daily_settlements')) {
            Schema::create('daily_settlements', function (Blueprint $table) {
                $table->id();
                $table->string('settlement_number', 50)->unique();
                $table->integer('dispatch_id');
                $table->date('settlement_date');
                $table->string('cashier_name', 100)->default('Main Cashier - Polonnaruwa Hub');
                $table->string('driver_name', 100);
                $table->string('sales_rep_name', 100);
                $table->decimal('total_invoiced_cash', 12, 2)->default(0.00);
                $table->decimal('total_invoiced_credit', 12, 2)->default(0.00);
                $table->decimal('physical_cash_handed', 12, 2)->default(0.00);
                $table->decimal('cash_variance', 12, 2)->default(0.00); // physical - invoiced
                $table->integer('loaded_cups')->default(0);
                $table->integer('sold_cups')->default(0);
                $table->integer('returned_cups')->default(0);
                $table->integer('damaged_cups')->default(0);
                $table->integer('expected_stock_cups')->default(0);
                $table->integer('actual_stock_checked_in')->default(0);
                $table->integer('stock_variance')->default(0);
                $table->integer('crates_issued_total')->default(0);
                $table->integer('crates_returned_total')->default(0);
                $table->integer('crates_variance')->default(0);
                $table->integer('curd_pots_returned')->default(0);
                $table->enum('status', ['BALANCED', 'VARIANCE_FLAGGED', 'APPROVED'])->default('BALANCED');
                $table->text('variance_notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_settlements');
        Schema::dropIfExists('crate_records');
        Schema::dropIfExists('stock_returns');
    }
};
