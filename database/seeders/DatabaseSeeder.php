<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed test user if not present
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Seed dairy categories
        $categories = [
            'Drinks' => Category::firstOrCreate(['name' => 'Drinks']),
            'Curd' => Category::firstOrCreate(['name' => 'Curd']),
            'Yoghurt' => Category::firstOrCreate(['name' => 'Yoghurt']),
            'Butter' => Category::firstOrCreate(['name' => 'Butter']),
        ];

        // Seed initial dairy catalog products if table is empty
        if (Product::count() === 0) {
            Product::insert([
                [
                    'name' => 'Vanilla Fresh Milk (Bottle)',
                    'sku' => 'DRY-MLK-01',
                    'category_id' => $categories['Drinks']->id,
                    'capacity' => '1 Liter',
                    'unit_price' => 420.00,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Natural Set Curd (Clay Pot)',
                    'sku' => 'DRY-CRD-02',
                    'category_id' => $categories['Curd']->id,
                    'capacity' => '450g',
                    'unit_price' => 360.00,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Strawberry Yogurt (Cup)',
                    'sku' => 'DRY-YGT-03',
                    'category_id' => $categories['Yoghurt']->id,
                    'capacity' => '200ml',
                    'unit_price' => 140.00,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Salted Pure Butter (Block)',
                    'sku' => 'DRY-BTR-04',
                    'category_id' => $categories['Butter']->id,
                    'capacity' => '200g',
                    'unit_price' => 750.00,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
