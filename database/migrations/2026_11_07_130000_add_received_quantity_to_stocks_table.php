<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('stocks')) {
            Schema::table('stocks', function (Blueprint $table) {
                if (! Schema::hasColumn('stocks', 'received_quantity')) {
                    $table->integer('received_quantity')->nullable()->after('quantity');
                }
            });

            // Backfill existing records so received_quantity = quantity
            if (Schema::hasColumn('stocks', 'received_quantity') && Schema::hasColumn('stocks', 'quantity')) {
                DB::table('stocks')->whereNull('received_quantity')->update([
                    'received_quantity' => DB::raw('quantity'),
                ]);
            }
        }

        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (! Schema::hasColumn('shipments', 'received_quantity')) {
                    $table->integer('received_quantity')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'received_quantity')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('received_quantity');
            });
        }

        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'received_quantity')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('received_quantity');
            });
        }
    }
};
