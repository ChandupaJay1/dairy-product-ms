<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('category', 100)->default('Set Yogurt (80g)')->after('flavor_name');
            });

            // Populate sensible categories
            DB::table('products')->where('sku_code', 'like', 'YOG-TRC%')->update(['category' => 'Traditional Curd & Clay Pot']);
            DB::table('products')->where('sku_code', 'not like', 'YOG-TRC%')->update(['category' => 'Fresh Cup Yogurt (80g)']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
