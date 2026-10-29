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
        // 1. Vehicles
        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('vehicle_number', 20)->unique();
                $table->string('vehicle_type', 100);
                $table->integer('capacity_cups');
                $table->string('cooling_system_status', 50)->default('NORMAL_COOLING');
                $table->enum('status', ['AVAILABLE', 'ASSIGNED', 'IN_TRANSIT', 'BREAKDOWN', 'MAINTENANCE'])->default('AVAILABLE');
                $table->integer('current_driver_id')->nullable();
                $table->integer('current_route_id')->nullable();
                $table->date('last_service_date')->nullable();
            });
        }

        // 2. Drivers
        if (!Schema::hasTable('drivers')) {
            Schema::create('drivers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('nic_number', 20)->nullable()->unique();
                $table->string('license_number', 30);
                $table->string('phone', 20);
                $table->integer('assigned_vehicle_id')->nullable();
                $table->enum('status', ['ACTIVE', 'ON_DELIVERY', 'STANDBY_RESCUE', 'OFF_DUTY'])->default('ACTIVE');
            });
        }

        // 3. Routes
        if (!Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->string('route_name', 100)->unique();
                $table->string('region_sector', 100);
                $table->string('primary_destination', 100);
                $table->decimal('estimated_distance_km', 8, 2)->default(0.00);
                $table->integer('assigned_vehicle_id')->nullable();
                $table->string('status', 20)->default('ACTIVE');
            });
        }

        // 4. Shops
        if (!Schema::hasTable('shops')) {
            Schema::create('shops', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('owner_name', 100);
                $table->string('phone', 20);
                $table->unsignedBigInteger('route_id')->nullable();
                $table->string('address', 255);
                $table->enum('credit_tier', ['Tier A', 'Tier B', 'Tier C', 'Cash Only']);
                $table->decimal('credit_limit', 12, 2)->default(0.00);
                $table->decimal('outstanding_balance', 12, 2)->default(0.00);
                $table->integer('payment_rating_percent')->default(100);
                $table->integer('overdue_days')->default(0);
                $table->boolean('is_active')->default(true);
            });
        }

        // 5. Products
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('flavor_name', 100);
                $table->decimal('unit_price', 10, 2);
                $table->integer('stock_warehouse')->default(10000);
                $table->string('sku_code', 50)->unique();
            });
        }

        // 6. Daily Dispatches
        if (!Schema::hasTable('dispatches')) {
            Schema::create('dispatches', function (Blueprint $table) {
                $table->id();
                $table->string('manifest_number', 50)->unique();
                $table->date('dispatch_date');
                $table->integer('vehicle_id');
                $table->integer('driver_id');
                $table->integer('sales_rep_id')->default(4);
                $table->integer('route_id');
                $table->integer('vanilla_load')->default(0);
                $table->integer('strawberry_load')->default(0);
                $table->integer('chocolate_load')->default(0);
                $table->integer('mango_load')->default(0);
                $table->integer('treacle_pot_load')->default(0);
                $table->integer('total_loaded_cups');
                $table->integer('remaining_cups');
                $table->integer('sold_cups')->default(0);
                $table->integer('damaged_cups')->default(0);
                $table->enum('status', ['PREPARING', 'IN_TRANSIT', 'SOS_BREAKDOWN', 'COMPLETED', 'SETTLED'])->default('IN_TRANSIT');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 7. Orders & Invoices
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->integer('dispatch_id');
                $table->integer('shop_id');
                $table->integer('sales_rep_id');
                $table->enum('payment_type', ['CASH', 'CREDIT']);
                $table->decimal('total_amount', 12, 2);
                $table->text('items_json');
                $table->string('digital_signature_hash', 64)->nullable();
                $table->longText('signature_svg_or_base64')->nullable();
                $table->boolean('otp_verified')->default(false);
                $table->string('otp_reference_code', 50)->nullable();
                $table->string('security_tamper_seal', 64);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 8. Emergency SOS Alerts
        if (!Schema::hasTable('sos_alerts')) {
            Schema::create('sos_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('sos_ticket_number', 50)->unique();
                $table->integer('dispatch_id');
                $table->integer('vehicle_id');
                $table->integer('driver_id');
                $table->decimal('latitude', 10, 6)->default(7.940300);
                $table->decimal('longitude', 10, 6)->default(81.018800);
                $table->string('location_landmark', 255);
                $table->string('breakdown_reason', 255);
                $table->decimal('chiller_temperature_celsius', 4, 1)->default(11.8);
                $table->integer('remaining_stock_at_incident');
                $table->enum('status', ['PENDING_RESCUE', 'RESCUE_DISPATCHED', 'STOCK_TRANSFERRED', 'RESOLVED'])->default('PENDING_RESCUE');
                $table->integer('rescue_vehicle_id')->nullable();
                $table->integer('rescue_driver_id')->nullable();
                $table->integer('transferred_good_stock')->default(0);
                $table->integer('spoiled_unusable_stock')->default(0);
                $table->text('rescue_handshake_notes')->nullable();
                $table->string('driver_signature_hash', 64)->nullable();
                $table->string('rescue_driver_signature_hash', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
            });
        }

        // 9. Immutable Audit Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamp('timestamp')->useCurrent();
                $table->string('action_type', 100);
                $table->string('actor_name', 100);
                $table->string('actor_role', 50);
                $table->string('target_entity', 100);
                $table->text('details_json');
                $table->string('ip_address', 50)->default('127.0.0.1');
                $table->string('security_seal', 64);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('dispatches');
        Schema::dropIfExists('products');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
    }
};
