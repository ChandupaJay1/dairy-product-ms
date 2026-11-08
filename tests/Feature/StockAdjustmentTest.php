<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('stock_adjustments table has expected columns and foreign keys', function () {
    expect(Schema::hasTable('stock_adjustments'))->toBeTrue();
    expect(Schema::hasColumns('stock_adjustments', [
        'id',
        'stock_id',
        'action_type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'authorized_by',
        'reason',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('stock model has adjustments relationship ordered by latest', function () {
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Relationship Test Product',
        'sku' => 'REL-TEST-001',
        'category_id' => $category->id,
        'capacity' => '500ml',
        'unit_price' => 120.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-TEST-REL',
        'received_quantity' => 100,
        'quantity' => 100,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $adj1 = StockAdjustment::create([
        'stock_id' => $stock->id,
        'action_type' => 'add',
        'quantity' => 10,
        'previous_quantity' => 100,
        'new_quantity' => 110,
        'authorized_by' => 'Manager One',
        'reason' => 'First adjustment',
    ]);

    $adj2 = StockAdjustment::create([
        'stock_id' => $stock->id,
        'action_type' => 'deduct',
        'quantity' => 5,
        'previous_quantity' => 110,
        'new_quantity' => 105,
        'authorized_by' => 'Manager Two',
        'reason' => 'Second adjustment',
    ]);

    expect($stock->adjustments)->toHaveCount(2);
    expect($stock->adjustments->first()->id)->toBe($adj2->id);
    expect($adj1->stock->id)->toBe($stock->id);
});

test('deleting a stock cascades and deletes its stock adjustments', function () {
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Cascade Test Product',
        'sku' => 'CAS-TEST-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-CASCADE-TEST',
        'received_quantity' => 50,
        'quantity' => 50,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    StockAdjustment::create([
        'stock_id' => $stock->id,
        'action_type' => 'add',
        'quantity' => 5,
        'previous_quantity' => 50,
        'new_quantity' => 55,
        'authorized_by' => 'Admin',
        'reason' => 'To be cascaded',
    ]);

    expect(StockAdjustment::where('stock_id', $stock->id)->count())->toBe(1);

    $stock->delete();

    expect(StockAdjustment::where('stock_id', $stock->id)->count())->toBe(0);
});

test('adjust endpoint validates required fields using bail rule', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Validation Test Product',
        'sku' => 'VAL-TEST-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-VALIDATION-TEST',
        'received_quantity' => 50,
        'quantity' => 50,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['action_type', 'quantity', 'authorized_by', 'reason']);
});

test('adjust endpoint prevents deducting more quantity than available stock', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Deduct Limit Product',
        'sku' => 'DED-TEST-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-DEDUCT-LIMIT',
        'received_quantity' => 20,
        'quantity' => 20,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), [
        'action_type' => 'deduct',
        'quantity' => 25,
        'authorized_by' => $user->name,
        'reason' => 'Excessive deduction attempt',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['quantity']);
    expect($response->json('errors.quantity.0'))->toContain('cannot exceed current available stock');
    expect($stock->fresh()->quantity)->toBe(20);
});

test('adjust endpoint successfully adds units and expands received_quantity capacity if exceeded', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Add Units Product',
        'sku' => 'ADD-TEST-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-ADD-TEST',
        'received_quantity' => 100,
        'quantity' => 100,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), [
        'action_type' => 'add',
        'quantity' => 15,
        'authorized_by' => $user->name,
        'reason' => 'Audit found surplus 15 units',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Stock inventory adjusted successfully.',
    ]);

    $freshStock = $stock->fresh();
    expect($freshStock->quantity)->toBe(115);
    expect($freshStock->received_quantity)->toBe(115);

    $adjustment = StockAdjustment::where('stock_id', $stock->id)->latest('id')->first();
    expect($adjustment)->not->toBeNull();
    expect($adjustment->action_type)->toBe('add');
    expect($adjustment->quantity)->toBe(15);
    expect($adjustment->previous_quantity)->toBe(100);
    expect($adjustment->new_quantity)->toBe(115);
    expect($adjustment->authorized_by)->toBe($user->name);
    expect($adjustment->reason)->toBe('Audit found surplus 15 units');
});

test('adjust endpoint successfully records spoilage and deduction', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Spoilage Test Product',
        'sku' => 'SPOIL-TEST-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-SPOILAGE-TEST',
        'received_quantity' => 100,
        'quantity' => 80,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), [
        'action_type' => 'spoilage',
        'quantity' => 12,
        'authorized_by' => $user->name,
        'reason' => 'Carton damaged in transit',
    ]);

    $response->assertOk();
    $freshStock = $stock->fresh();
    expect($freshStock->quantity)->toBe(68);
    expect($freshStock->received_quantity)->toBe(100); // unchanged

    $adjustment = StockAdjustment::where('stock_id', $stock->id)->latest('id')->first();
    expect($adjustment->action_type)->toBe('spoilage');
    expect($adjustment->quantity)->toBe(12);
    expect($adjustment->previous_quantity)->toBe(80);
    expect($adjustment->new_quantity)->toBe(68);
});

test('adjust endpoint accepts Spoilage / Damaged Goods label and performs deduction', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Spoilage Label Product',
        'sku' => 'SPOIL-LBL-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-SPOIL-LBL',
        'received_quantity' => 50,
        'quantity' => 50,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), [
        'action_type' => 'Spoilage / Damaged Goods',
        'quantity' => 10,
        'authorized_by' => $user->name,
        'reason' => 'Damaged during unloading',
    ]);

    $response->assertOk();
    $freshStock = $stock->fresh();
    expect($freshStock->quantity)->toBe(40);

    $adjustment = StockAdjustment::where('stock_id', $stock->id)->latest('id')->first();
    expect($adjustment->action_type)->toBe('spoilage');
    expect($adjustment->quantity)->toBe(10);
    expect($adjustment->previous_quantity)->toBe(50);
    expect($adjustment->new_quantity)->toBe(40);
});

test('adjust endpoint rejects customer return action type', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Return Reject Product',
        'sku' => 'RET-REJ-001',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-RET-REJ',
        'received_quantity' => 50,
        'quantity' => 50,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.adjust', $stock), [
        'action_type' => 'return',
        'quantity' => 5,
        'authorized_by' => $user->name,
        'reason' => 'Customer returned bottles',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['action_type']);
});

test('stocks management view renders adjustment history button, badge and modals', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy']);
    $product = Product::create([
        'name' => 'Strawberry Milkshake 500ml',
        'sku' => 'MILK-500-STRAW',
        'category_id' => $category->id,
        'capacity' => '500ml',
        'unit_price' => 250.00,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-VIEW-TEST-99',
        'received_quantity' => 200,
        'quantity' => 180,
        'manufacture_date' => '2026-01-01',
        'expiry_date' => '2026-12-31',
        'issued_date' => '2026-01-02',
    ]);

    StockAdjustment::create([
        'stock_id' => $stock->id,
        'action_type' => 'deduct',
        'quantity' => 20,
        'previous_quantity' => 200,
        'new_quantity' => 180,
        'authorized_by' => 'Inventory Supervisor',
        'reason' => 'Quality lab testing sample',
    ]);

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertSee('Stock Adjustment History', false);
    $response->assertSee('Audit logs and inventory correction records.', false);
    $response->assertSee('openHistory', false);
    $response->assertSee('showHistoryModal', false);
    $response->assertSee('View Adjustment History / Audit Logs', false);
    $response->assertSee('Adjust Stock Inventory', false);
    $response->assertSee('submitAdjustForm', false);
    $response->assertSee('<option value="spoilage">Spoilage / Damaged Goods</option>', false);
    $response->assertDontSee('Customer Return / Quality Restock', false);
    $response->assertDontSee('Spoilage / Damaged Goods Write-Off (- Deduct Units)', false);
    $response->assertSee('Quality lab testing sample', false);
});

test('stocks management blade view does not use font-mono styling anywhere', function () {
    $bladeContent = file_get_contents(resource_path('views/stocks/index.blade.php'));
    expect($bladeContent)->not->toContain('font-mono');
    expect($bladeContent)->not->toContain('monospace');
});
