<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispatches') && ! Schema::hasColumn('dispatches', 'items_json')) {
            Schema::table('dispatches', function (Blueprint $table) {
                $table->longText('items_json')->nullable()->after('treacle_pot_load');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dispatches') && Schema::hasColumn('dispatches', 'items_json')) {
            Schema::table('dispatches', function (Blueprint $table) {
                $table->dropColumn('items_json');
            });
        }
    }
};
