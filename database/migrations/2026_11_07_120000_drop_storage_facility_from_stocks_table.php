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
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'storage_facility')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('storage_facility');
            });
        }

        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'storage_facility')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('storage_facility');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks') && !Schema::hasColumn('stocks', 'storage_facility')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->string('storage_facility')->nullable();
            });
        }

        if (Schema::hasTable('shipments') && !Schema::hasColumn('shipments', 'storage_facility')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('storage_facility')->nullable();
            });
        }
    }
};

