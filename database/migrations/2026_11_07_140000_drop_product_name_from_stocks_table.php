<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'product_name')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('product_name');
            });
        }

        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'product_name')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('product_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks') && ! Schema::hasColumn('stocks', 'product_name')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->string('product_name')->nullable();
            });
        }

        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'product_name')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('product_name')->nullable();
            });
        }
    }
};
