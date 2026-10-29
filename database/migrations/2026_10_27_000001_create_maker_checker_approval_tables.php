<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Maker-Checker Approval Protocol.
     */
    public function up(): void
    {
        // 1. Payments Table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->unsignedBigInteger('shop_id');
                $table->integer('dispatch_id')->nullable();
                $table->string('sales_rep_name', 100)->default('Field Sales Rep');
                $table->decimal('payment_amount', 12, 2);
                $table->string('payment_method', 50)->default('CASH');
                $table->text('notes')->nullable();
                $table->enum('status', ['PENDING', 'CONFIRMED', 'REJECTED'])->default('PENDING');
                $table->string('confirmed_by', 100)->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. Add approval status to stock_returns
        if (Schema::hasTable('stock_returns') && !Schema::hasColumn('stock_returns', 'status')) {
            Schema::table('stock_returns', function (Blueprint $table) {
                $table->enum('status', ['PENDING', 'CONFIRMED', 'REJECTED'])->default('PENDING')->after('notes');
                $table->string('confirmed_by', 100)->nullable()->after('status');
                $table->text('rejection_reason')->nullable()->after('confirmed_by');
                $table->timestamp('confirmed_at')->nullable()->after('rejection_reason');
            });
        }

        // 3. Add approval status to shops
        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'approval_status')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('APPROVED')->after('is_active');
                $table->string('approved_by', 100)->nullable()->after('approval_status');
                $table->text('rejection_reason')->nullable()->after('approved_by');
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            });
        }

        // 4. Add approval status to orders
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('status', ['PENDING_APPROVAL', 'CONFIRMED', 'REJECTED'])->default('CONFIRMED')->after('security_tamper_seal');
                $table->string('approved_by', 100)->nullable()->after('status');
                $table->text('rejection_reason')->nullable()->after('approved_by');
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');

        if (Schema::hasTable('stock_returns') && Schema::hasColumn('stock_returns', 'status')) {
            Schema::table('stock_returns', function (Blueprint $table) {
                $table->dropColumn(['status', 'confirmed_by', 'rejection_reason', 'confirmed_at']);
            });
        }

        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'approval_status')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn(['approval_status', 'approved_by', 'rejection_reason', 'approved_at']);
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['status', 'approved_by', 'rejection_reason', 'approved_at']);
            });
        }
    }
};
