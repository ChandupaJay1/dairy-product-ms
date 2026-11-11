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
        if (Schema::hasTable('stocks')) {
            Schema::table('stocks', function (Blueprint $table) {
                if (! Schema::hasColumn('stocks', 'manufacture_date')) {
                    $table->date('manufacture_date')->nullable()->after('expiry_date');
                }
                if (! Schema::hasColumn('stocks', 'issued_date')) {
                    $table->date('issued_date')->nullable()->after('manufacture_date');
                }
            });
        }

        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (! Schema::hasColumn('shipments', 'manufacture_date')) {
                    $table->date('manufacture_date')->nullable();
                }
                if (! Schema::hasColumn('shipments', 'issued_date')) {
                    $table->date('issued_date')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks')) {
            Schema::table('stocks', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('stocks', 'manufacture_date')) {
                    $columns[] = 'manufacture_date';
                }
                if (Schema::hasColumn('stocks', 'issued_date')) {
                    $columns[] = 'issued_date';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('shipments', 'manufacture_date')) {
                    $columns[] = 'manufacture_date';
                }
                if (Schema::hasColumn('shipments', 'issued_date')) {
                    $columns[] = 'issued_date';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
