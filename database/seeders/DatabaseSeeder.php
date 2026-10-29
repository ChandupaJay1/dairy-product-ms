<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with roles, fleet, and operational test data.
     */
    public function run(): void
    {
        // 1. Role-Based Users Authentication
        $defaultPassword = Hash::make('password');

        $users = [
            [
                'name' => 'Front Office Admin',
                'email' => 'admin@pulse.lk',
                'password' => $defaultPassword,
                'role' => User::ROLE_ADMIN,
                'phone' => '071-1122334',
            ],
            [
                'name' => 'Saman Kumara (Field Rep)',
                'email' => 'rep@pulse.lk',
                'password' => $defaultPassword,
                'role' => User::ROLE_SALES_REP,
                'phone' => '077-1234567',
            ],
            [
                'name' => 'Kamal Gunaratne (Driver)',
                'email' => 'driver@pulse.lk',
                'password' => $defaultPassword,
                'role' => User::ROLE_DRIVER,
                'phone' => '071-8845210',
            ],
            [
                'name' => 'Dilani Perera (Cashier)',
                'email' => 'cashier@pulse.lk',
                'password' => $defaultPassword,
                'role' => User::ROLE_CASHIER,
                'phone' => '078-9988776',
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Ensure Himasha Shehan has admin privileges
        User::where('email', 'himashashehan0813@gmail.com')->update(['role' => User::ROLE_ADMIN]);

        $repUser = User::where('email', 'rep@pulse.lk')->first();

        // 2. Sales Representatives Table
        if (DB::table('sales_representatives')->count() === 0) {
            DB::table('sales_representatives')->insert([
                [
                    'user_id' => $repUser ? $repUser->id : null,
                    'employee_code' => 'REP-001',
                    'name' => 'Saman Kumara',
                    'phone' => '077-1234567',
                    'assigned_route_sector' => 'Hingurakgoda & Minneriya',
                    'commission_rate_percent' => 5.00,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'user_id' => null,
                    'employee_code' => 'REP-002',
                    'name' => 'Chaminda Bandara',
                    'phone' => '077-5544332',
                    'assigned_route_sector' => 'Kaduruwela & Old Town',
                    'commission_rate_percent' => 5.00,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'user_id' => null,
                    'employee_code' => 'REP-003',
                    'name' => 'Niroshan Weerasinghe',
                    'phone' => '076-2233441',
                    'assigned_route_sector' => 'Medirigiriya & Kaudulla',
                    'commission_rate_percent' => 4.50,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }

        // 3. Vehicles
        if (DB::table('vehicles')->count() === 0) {
            DB::table('vehicles')->insert([
                ['vehicle_number' => 'NC-GA-4521', 'vehicle_type' => 'Refrigerated Cold-Truck 3.5T', 'capacity_cups' => 2500, 'cooling_system_status' => 'NORMAL_COOLING', 'status' => 'IN_TRANSIT', 'last_service_date' => '2026-10-10'],
                ['vehicle_number' => 'NC-GB-8812', 'vehicle_type' => 'Insulated Chilled Van 1.8T', 'capacity_cups' => 1800, 'cooling_system_status' => 'NORMAL_COOLING', 'status' => 'AVAILABLE', 'last_service_date' => '2026-10-15'],
                ['vehicle_number' => 'WP-LD-6023', 'vehicle_type' => 'High-Capacity Cold Lorry 4.0T', 'capacity_cups' => 3200, 'cooling_system_status' => 'NORMAL_COOLING', 'status' => 'AVAILABLE', 'last_service_date' => '2026-10-08'],
                ['vehicle_number' => 'NC-GA-1190', 'vehicle_type' => 'Rapid Stock Rescue Van 2.0T', 'capacity_cups' => 2000, 'cooling_system_status' => 'NORMAL_COOLING', 'status' => 'AVAILABLE', 'last_service_date' => '2026-10-18']
            ]);
        }

        // 4. Drivers
        if (DB::table('drivers')->count() === 0) {
            DB::table('drivers')->insert([
                ['name' => 'Kamal Gunaratne', 'nic_number' => '781920345V', 'license_number' => 'B-458921', 'phone' => '071-8845210', 'assigned_vehicle_id' => 1, 'status' => 'ON_DELIVERY'],
                ['name' => 'Sunil Jayasinghe', 'nic_number' => '842340567V', 'license_number' => 'B-891024', 'phone' => '072-9901452', 'assigned_vehicle_id' => 4, 'status' => 'STANDBY_RESCUE'],
                ['name' => 'Nimal Perera', 'nic_number' => '801239841V', 'license_number' => 'B-128945', 'phone' => '077-3322119', 'assigned_vehicle_id' => 2, 'status' => 'ACTIVE'],
                ['name' => 'Pradeep Bandara', 'nic_number' => '861948271V', 'license_number' => 'B-772910', 'phone' => '075-4499112', 'assigned_vehicle_id' => 3, 'status' => 'ACTIVE']
            ]);
        }

        // 5. Routes
        if (DB::table('routes')->count() === 0) {
            DB::table('routes')->insert([
                ['route_name' => 'Route 01 - Hingurakgoda Express', 'region_sector' => 'Hingurakgoda & Minneriya', 'primary_destination' => 'Hingurakgoda Town Center', 'estimated_distance_km' => 42.50, 'assigned_vehicle_id' => 1],
                ['route_name' => 'Route 02 - Kaduruwela Commercial Belt', 'region_sector' => 'Kaduruwela & Old Town', 'primary_destination' => 'Kaduruwela Main Wholesale Market', 'estimated_distance_km' => 28.00, 'assigned_vehicle_id' => 2],
                ['route_name' => 'Route 03 - Medirigiriya Dairy Corridor', 'region_sector' => 'Medirigiriya & Kaudulla', 'primary_destination' => 'Medirigiriya Vatadage Junction', 'estimated_distance_km' => 65.00, 'assigned_vehicle_id' => 3],
                ['route_name' => 'Route 04 - Dimbulagala & Welikanda Link', 'region_sector' => 'Dimbulagala & Mahaweli Zone', 'primary_destination' => 'Dimbulagala Buddhist Center Junction', 'estimated_distance_km' => 78.00, 'assigned_vehicle_id' => 4]
            ]);
        }

        // 6. Shops
        if (DB::table('shops')->count() === 0) {
            DB::table('shops')->insert([
                ['name' => 'Kaduruwela Central Super', 'owner_name' => 'Mr. S. Wickramasinghe', 'phone' => '027-2224501', 'route_id' => 2, 'address' => 'No. 44, Main Street, Kaduruwela', 'credit_tier' => 'Tier A', 'credit_limit' => 60000.00, 'outstanding_balance' => 18500.00, 'payment_rating_percent' => 98, 'overdue_days' => 0],
                ['name' => 'Hingurakgoda Milk House', 'owner_name' => 'Mr. Bandula Jayasuriya', 'phone' => '027-2246102', 'route_id' => 1, 'address' => 'Opposite Clock Tower, Hingurakgoda', 'credit_tier' => 'Tier A', 'credit_limit' => 50000.00, 'outstanding_balance' => 12000.00, 'payment_rating_percent' => 95, 'overdue_days' => 0],
                ['name' => 'Medirigiriya City Grocers', 'owner_name' => 'Mrs. Chandani Silva', 'phone' => '027-2248303', 'route_id' => 3, 'address' => 'Post Office Road, Medirigiriya', 'credit_tier' => 'Tier B', 'credit_limit' => 30000.00, 'outstanding_balance' => 22500.00, 'payment_rating_percent' => 82, 'overdue_days' => 4],
                ['name' => 'Minneriya Junction Dairy Mart', 'owner_name' => 'Mr. Ruwan Fernando', 'phone' => '027-2231204', 'route_id' => 1, 'address' => 'Habarana Road, Minneriya', 'credit_tier' => 'Tier B', 'credit_limit' => 25000.00, 'outstanding_balance' => 4500.00, 'payment_rating_percent' => 91, 'overdue_days' => 0],
                ['name' => 'Dimbulagala Farmers Cooperative', 'owner_name' => 'Mr. K. Somapala', 'phone' => '027-2259105', 'route_id' => 4, 'address' => 'Temple Road, Dimbulagala', 'credit_tier' => 'Tier C', 'credit_limit' => 10000.00, 'outstanding_balance' => 9200.00, 'payment_rating_percent' => 70, 'overdue_days' => 12],
                ['name' => 'Welikanda Raja Stores', 'owner_name' => 'Mr. H. M. Karunaratne', 'phone' => '027-2252406', 'route_id' => 4, 'address' => 'Station Junction, Welikanda', 'credit_tier' => 'Tier C', 'credit_limit' => 10000.00, 'outstanding_balance' => 3100.00, 'payment_rating_percent' => 85, 'overdue_days' => 0],
                ['name' => 'Giritale Lakeside Tuck Shop', 'owner_name' => 'Mr. D. Senanayake', 'phone' => '027-2241907', 'route_id' => 1, 'address' => 'Near Giritale Tank Bund', 'credit_tier' => 'Cash Only', 'credit_limit' => 0.00, 'outstanding_balance' => 0.00, 'payment_rating_percent' => 100, 'overdue_days' => 0],
                ['name' => 'Thamankaduwa Peoples Super', 'owner_name' => 'Mrs. M. Abeyratne', 'phone' => '027-2228808', 'route_id' => 2, 'address' => 'Hospital Road, Polonnaruwa New Town', 'credit_tier' => 'Tier A', 'credit_limit' => 45000.00, 'outstanding_balance' => 8000.00, 'payment_rating_percent' => 96, 'overdue_days' => 0]
            ]);
        }

        // 7. Products
        if (DB::table('products')->count() === 0) {
            DB::table('products')->insert([
                ['flavor_name' => 'Fresh Vanilla Yogurt (80g)', 'unit_price' => 80.00, 'stock_warehouse' => 12000, 'sku_code' => 'YOG-VAN-80'],
                ['flavor_name' => 'Sweet Strawberry Yogurt (80g)', 'unit_price' => 90.00, 'stock_warehouse' => 8500, 'sku_code' => 'YOG-STR-80'],
                ['flavor_name' => 'Creamy Chocolate Yogurt (80g)', 'unit_price' => 95.00, 'stock_warehouse' => 6000, 'sku_code' => 'YOG-CHO-80'],
                ['flavor_name' => 'Tropical Mango Yogurt (80g)', 'unit_price' => 95.00, 'stock_warehouse' => 5500, 'sku_code' => 'YOG-MAN-80'],
                ['flavor_name' => 'Traditional Curd & Kithul Treacle Pot (180g)', 'unit_price' => 160.00, 'stock_warehouse' => 3000, 'sku_code' => 'YOG-TRC-180']
            ]);
        }

        // 8. Daily Dispatches
        if (DB::table('dispatches')->count() === 0) {
            DB::table('dispatches')->insert([
                [
                    'manifest_number' => 'MNF-' . date('Ymd') . '-01',
                    'dispatch_date' => date('Y-m-d'),
                    'vehicle_id' => 1,
                    'driver_id' => 1,
                    'sales_rep_id' => 1,
                    'route_id' => 1,
                    'vanilla_load' => 600,
                    'strawberry_load' => 350,
                    'chocolate_load' => 250,
                    'mango_load' => 200,
                    'treacle_pot_load' => 100,
                    'total_loaded_cups' => 1500,
                    'remaining_cups' => 1320,
                    'sold_cups' => 180,
                    'damaged_cups' => 0,
                    'status' => 'IN_TRANSIT',
                    'notes' => 'Morning cold-chain dispatch. Chiller temperature verified at 4.1°C.'
                ]
            ]);
        }

        // 9. Call Operations Seeder
        $this->call(OperationsSeeder::class);
    }
}
