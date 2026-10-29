<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Stock Returns (Expired & Damaged)
        if (DB::table('stock_returns')->count() === 0) {
            DB::table('stock_returns')->insert([
                [
                    'return_number' => 'RET-20261025-001',
                    'shop_id' => 1,
                    'dispatch_id' => 1,
                    'sales_rep_id' => 4,
                    'flavor_name' => 'Fresh Vanilla Yogurt (80g)',
                    'batch_number' => 'LOT-20261010-VAN',
                    'expiry_date' => '2026-10-24',
                    'quantity_cups' => 15,
                    'unit_price' => 80.00,
                    'total_value' => 1200.00,
                    'return_reason' => 'EXPIRED',
                    'settlement_action' => 'CREDIT_NOTE',
                    'notes' => 'Old batch remaining on bottom shelf. Verified cold chain intact but passed 14-day date.',
                    'created_at' => now()->subHours(5)
                ],
                [
                    'return_number' => 'RET-20261025-002',
                    'shop_id' => 3,
                    'dispatch_id' => 1,
                    'sales_rep_id' => 4,
                    'flavor_name' => 'Sweet Strawberry Yogurt (80g)',
                    'batch_number' => 'LOT-20261014-STR',
                    'expiry_date' => '2026-10-28',
                    'quantity_cups' => 8,
                    'unit_price' => 90.00,
                    'total_value' => 720.00,
                    'return_reason' => 'LEAK_DAMAGED',
                    'settlement_action' => 'REPLACEMENT',
                    'notes' => 'Foil seal puncture during shop refrigeration restocking. Replaced on spot.',
                    'created_at' => now()->subHours(3)
                ]
            ]);
        }

        // 2. Initial Crate Records
        if (DB::table('crate_records')->count() === 0) {
            DB::table('crate_records')->insert([
                [
                    'dispatch_id' => 1,
                    'shop_id' => 1,
                    'crates_issued' => 4,
                    'crates_collected' => 4,
                    'curd_pots_issued' => 2,
                    'curd_pots_collected' => 2,
                    'pot_deposit_fee' => 0.00,
                    'notes' => 'Kaduruwela Super complete empty crate swap.',
                    'created_at' => now()->subHours(4)
                ],
                [
                    'dispatch_id' => 1,
                    'shop_id' => 2,
                    'crates_issued' => 3,
                    'crates_collected' => 2,
                    'curd_pots_issued' => 1,
                    'curd_pots_collected' => 0,
                    'pot_deposit_fee' => 50.00,
                    'notes' => '1 crate and 1 curd pot retained at Hingurakgoda Milk House until tomorrow morning.',
                    'created_at' => now()->subHours(2)
                ]
            ]);
        }

        // 3. Initial Past Daily Settlement Record
        if (DB::table('daily_settlements')->count() === 0) {
            DB::table('daily_settlements')->insert([
                [
                    'settlement_number' => 'SET-20261024-001',
                    'dispatch_id' => 1,
                    'settlement_date' => '2026-10-24',
                    'cashier_name' => 'Mr. P. Rajapaksa (Chief Cashier)',
                    'driver_name' => 'Kamal Gunaratne',
                    'sales_rep_name' => 'Nimal Perera',
                    'total_invoiced_cash' => 24500.00,
                    'total_invoiced_credit' => 18000.00,
                    'physical_cash_handed' => 24500.00,
                    'cash_variance' => 0.00,
                    'loaded_cups' => 1500,
                    'sold_cups' => 1420,
                    'returned_cups' => 15,
                    'damaged_cups' => 5,
                    'expected_stock_cups' => 75,
                    'actual_stock_checked_in' => 75,
                    'stock_variance' => 0,
                    'crates_issued_total' => 50,
                    'crates_returned_total' => 48,
                    'crates_variance' => -2,
                    'curd_pots_returned' => 12,
                    'status' => 'BALANCED',
                    'variance_notes' => 'All cash reconciled 100%. 2 plastic crates held at Giritale partner shop with deposit.',
                    'created_at' => now()->subDay()
                ]
            ]);
        }
    }
}
