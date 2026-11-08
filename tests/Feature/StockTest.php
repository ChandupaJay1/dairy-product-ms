<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('stocks table has manufacture_date and issued_date columns and does not have storage_facility', function () {
    expect(Schema::hasTable('stocks'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'manufacture_date'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'issued_date'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'expiry_date'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'batch_number'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'quantity'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'received_quantity'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'product_id'))->toBeTrue();
    expect(Schema::hasColumn('stocks', 'product_name'))->toBeFalse();
    expect(Schema::hasColumn('stocks', 'storage_facility'))->toBeFalse();
});

test('stocks management page displays manufacture date and issued date fields and does not display storage facility', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertSee('Receive New Stock Shipment', false);
    $response->assertSee('Manufacture Date', false);
    $response->assertSee('name="manufacture_date"', false);
    $response->assertSee('Expiry Date', false);
    $response->assertSee('name="expiry_date"', false);
    $response->assertSee('Issued Date', false);
    $response->assertSee('name="issued_date"', false);
    $response->assertSee('col-span-full', false);
    $response->assertSee('type="date"', false);
    $response->assertDontSee('Storage Facility / Bin', false);
    $response->assertDontSee('name="storage_facility"', false);
});

test('stock shipment creation requires manufacture_date and issued_date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'batch_number' => 'BATCH-TEST-001',
        'quantity' => 100,
        'expiry_date' => '2026-12-31',
        // manufacture_date and issued_date omitted
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['manufacture_date', 'issued_date']);
});

test('stock shipment creation validates manufacture_date and issued_date as dates', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'batch_number' => 'BATCH-TEST-002',
        'quantity' => 50,
        'expiry_date' => '2026-12-31',
        'manufacture_date' => 'not-a-valid-date',
        'issued_date' => 'invalid-date',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['manufacture_date', 'issued_date']);
});

test('authenticated user can store stock shipment with manufacture_date and issued_date', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);
    $product = Product::create([
        'name' => 'Stock Test Milk',
        'sku' => 'DRY-STK-01',
        'category_id' => $category->id,
        'capacity' => '1 Liter',
        'unit_price' => 450.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-2026-X',
        'quantity' => 250,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-11-20',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-2026-X',
        'quantity' => 250,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-11-20',
    ]);
});

test('standard form submission redirects with success flash message', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'General']);
    $product = Product::firstOrCreate(['sku' => 'DRY-FORM-01'], [
        'name' => 'Standard Form Milk',
        'category_id' => $category->id,
        'capacity' => '1 Liter',
        'unit_price' => 200,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->post(route('stocks.store'), [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-FORM-001',
        'quantity' => 80,
        'manufacture_date' => '2026-11-02',
        'issued_date' => '2026-11-06',
        'expiry_date' => '2026-11-25',
    ]);

    $response->assertRedirect(route('stocks.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-FORM-001',
        'quantity' => 80,
        'received_quantity' => 80,
        'manufacture_date' => '2026-11-02',
        'issued_date' => '2026-11-06',
    ]);
});

test('receive stock modal has single error extraction and state reset logic', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertSee('Object.keys(data.errors)[0]', false);
    $response->assertSee('[firstKey]: data.errors[firstKey][0]', false);
    $response->assertSee('@click="receiveErrors = {}"', false);
});

test('add receive stock button is enabled when products exist in database', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);
    Product::firstOrCreate([
        'sku' => 'DRY-BTN-01',
    ], [
        'name' => 'Button Active Milk',
        'category_id' => $category->id,
        'capacity' => '1 Liter',
        'unit_price' => 400.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertViewHas('hasProducts', true);
    $response->assertSee('@click="showReceiveModal = true"', false);
    $response->assertSee('hover:scale-[1.02]', false);
    $response->assertDontSee('opacity-50 cursor-not-allowed', false);
    $response->assertDontSee('title="Please register at least one product before receiving stock"', false);
});

test('add receive stock button is disabled with opacity-50 and cursor-not-allowed when no products exist', function () {
    $user = User::factory()->create();
    Stock::query()->delete();
    Product::query()->delete();

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertViewHas('hasProducts', false);
    $response->assertSee('title="Please register at least one product before receiving stock"', false);
    $response->assertSee('opacity-50 cursor-not-allowed', false);
    $response->assertSee('shadow-none', false);
    $response->assertDontSee('@click="showReceiveModal = true"', false);
    $response->assertSee('disabled', false);
    $response->assertSee('shadow-none', false);
    $response->assertDontSee('@click="showReceiveModal = true"', false);
});

test('receive stock shipment modal renders as 2-step wizard with selectable product cards and step controls', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Dairy Drinks']);
    $product = Product::firstOrCreate([
        'sku' => 'DRY-WZ-01',
    ], [
        'name' => 'Wizard Test Whole Milk',
        'category_id' => $category->id,
        'capacity' => '1 Liter',
        'unit_price' => 380.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    // Step state tracking
    $response->assertSee('x-data="{ step: 1, selectedProductId: null, searchQuery: \'\' }"', false);
    // Step 1 wrapper & card selection
    $response->assertSee('x-show="step === 1"', false);
    $response->assertSee('selectedProductId = ' . $product->id, false);
    $response->assertSee('Wizard Test Whole Milk', false);
    $response->assertSee('border-lime-500', false);
    $response->assertSee('Next', false);
    $response->assertSee('@click="step = 2"', false);
    $response->assertSee(':disabled="!selectedProductId"', false);
    $response->assertSee('Cancel', false);

    // Step 2 wrapper & controls
    $response->assertSee('x-show="step === 2"', false);
    $response->assertSee('x-cloak', false);
    $response->assertSee('<input type="hidden" name="product_id" :value="selectedProductId"', false);
    $response->assertSee('name="batch_number"', false);
    $response->assertSee('name="received_quantity"', false);
    $response->assertSee('name="manufacture_date"', false);
    $response->assertSee('name="expiry_date"', false);
    $response->assertSee('name="issued_date"', false);
    $response->assertSee('Back', false);
    $response->assertSee('@click="step = 1"', false);
    $response->assertSee('Confirm Inbound Stock', false);
});

test('step 1 of receive stock modal includes real-time product search filter and empty state', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Organic Fresh']);
    $product = Product::firstOrCreate([
        'sku' => 'DRY-SRCH-99',
    ], [
        'name' => 'Artisan Goat Milk',
        'category_id' => $category->id,
        'capacity' => '500ml',
        'unit_price' => 520.00,
        'status' => 'Active',
    ]);

    expect($product->product_name)->toBe('Artisan Goat Milk');
    expect($product->sku_number)->toBe('DRY-SRCH-99');

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    // Search input
    $response->assertSee('placeholder="Search products by name or SKU..."', false);
    $response->assertSee('x-model="searchQuery"', false);
    
    // Live filtering in card loop
    $expectedFilterSnippet = "x-show=\"searchQuery === '' || 'artisan goat milk'.includes(searchQuery.toLowerCase()) || 'dry-srch-99'.includes(searchQuery.toLowerCase())\"";
    $response->assertSee($expectedFilterSnippet, false);

    // Empty state text
    $response->assertSee('No products found matching your search', false);
});

test('stock shipment creation validates expiry_date after manufacture_date', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Beverages']);
    $product = Product::firstOrCreate(['sku' => 'DRY-DATE-01'], [
        'name' => 'Date Check Milk',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 300,
        'status' => 'Active',
    ]);

    // expiry_date earlier than manufacture_date
    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-DATE-ERR',
        'received_quantity' => 10,
        'manufacture_date' => '2026-11-10',
        'expiry_date' => '2026-11-05',
        'issued_date' => '2026-11-08',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['expiry_date']);
    expect($response->json('errors.expiry_date.0'))->toContain('after');
});

test('stock shipment creation requires valid product_id and positive received_quantity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'product_id' => 999999, // non-existent product
        'batch_number' => 'BATCH-INV-01',
        'received_quantity' => 0,
        'manufacture_date' => '2026-11-01',
        'expiry_date' => '2026-11-20',
        'issued_date' => '2026-11-05',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['product_id', 'received_quantity']);
});

test('stock shipment creation via ajax stores data and returns json message with 201 status', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Cheese & Dairy']);
    $product = Product::firstOrCreate(['sku' => 'DRY-AJAX-01'], [
        'name' => 'Artisan Cheddar Block',
        'category_id' => $category->id,
        'capacity' => '250g',
        'unit_price' => 750,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->postJson(route('stocks.store'), [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-AJAX-SUCCESS',
        'received_quantity' => 45,
        'manufacture_date' => '2026-11-01',
        'expiry_date' => '2026-12-01',
        'issued_date' => '2026-11-05',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Stock received successfully',
        ]);

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'batch_number' => 'BATCH-AJAX-SUCCESS',
        'received_quantity' => 45,
        'quantity' => 45,
        'manufacture_date' => '2026-11-01',
        'expiry_date' => '2026-12-01',
        'issued_date' => '2026-11-05',
    ]);

    $stock = Stock::where('batch_number', 'BATCH-AJAX-SUCCESS')->first();
    expect($stock)->not->toBeNull();
    expect($stock->product)->not->toBeNull();
    expect($stock->product->id)->toBe($product->id);
    expect($stock->product->name)->toBe('Artisan Cheddar Block');
});

test('stocks table is normalized without product_name column and Stock fillable does not include product_name', function () {
    expect(Schema::hasColumn('stocks', 'product_name'))->toBeFalse();

    $stock = new Stock();
    expect($stock->getFillable())->not->toContain('product_name');
    expect($stock->getFillable())->toContain('product_id', 'batch_number', 'received_quantity', 'manufacture_date', 'expiry_date', 'issued_date');
});

test('stocks dashboard loads summary statistics and renders stock cards dynamically from database', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Artisan Cheeses']);
    $product1 = Product::create([
        'name' => 'Farm Fresh Gouda',
        'sku' => 'DRY-GOU-01',
        'category_id' => $category->id,
        'capacity' => 'Wheel (2kg)',
        'unit_price' => 1200.00,
        'status' => 'Active',
    ]);

    $product2 = Product::create([
        'name' => 'Alpine Goat Cheese',
        'sku' => 'DRY-ALP-02',
        'category_id' => $category->id,
        'capacity' => 'Block (200g)',
        'unit_price' => 650.00,
        'status' => 'Active',
    ]);

    // Batch 1: In stock (quantity: 150 > 50, expiring in 30 days)
    $stock1 = Stock::create([
        'product_id' => $product1->id,
        'batch_number' => 'BATCH-GOUDA-101',
        'quantity' => 150,
        'received_quantity' => 200,
        'manufacture_date' => now()->subDays(10)->toDateString(),
        'issued_date' => now()->subDays(5)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    // Batch 2: Low stock (quantity: 20 <= 50, expiring in 25 days)
    $stock2 = Stock::create([
        'product_id' => $product2->id,
        'batch_number' => 'BATCH-GOAT-202',
        'quantity' => 20,
        'received_quantity' => 100,
        'manufacture_date' => now()->subDays(12)->toDateString(),
        'issued_date' => now()->subDays(6)->toDateString(),
        'expiry_date' => now()->addDays(25)->toDateString(),
    ]);

    // Batch 3: Expiring soon (quantity: 60 > 50, expiring in 3 days)
    $stock3 = Stock::create([
        'product_id' => $product1->id,
        'batch_number' => 'BATCH-GOUDA-EXP3',
        'quantity' => 60,
        'received_quantity' => 80,
        'manufacture_date' => now()->subDays(20)->toDateString(),
        'issued_date' => now()->subDays(10)->toDateString(),
        'expiry_date' => now()->addDays(3)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();

    // Verify view variables passed
    $response->assertViewHas('totalUnits', 230); // 150 + 20 + 60
    $response->assertViewHas('totalBatches', 3);
    $response->assertViewHas('lowStockCount', 1);
    $response->assertViewHas('expiringCount', 1);
    $response->assertViewHas('inStockCount', 1);

    // Verify dynamic stats rendering in HTML
    $response->assertSee('230'); // total units
    $response->assertSee('Across 3 Batches');

    // Verify dynamic stock cards content
    $response->assertSee('Farm Fresh Gouda');
    $response->assertSee('DRY-GOU-01');
    $response->assertSee('BATCH-GOUDA-101');
    $response->assertSee('Alpine Goat Cheese');
    $response->assertSee('DRY-ALP-02');
    $response->assertSee('BATCH-GOAT-202');
    $response->assertSee('BATCH-GOUDA-EXP3');
    $response->assertSee('Artisan Cheeses');

    // Verify status badges
    $response->assertSee('In Stock');
    $response->assertSee('Low Stock');
    $response->assertSee('Expiring Soon');
});

test('stocks management page renders delete stock button with click handler and custom alpine confirmation modal', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);
    $product = Product::create([
        'name' => 'Delete Button Test Milk',
        'sku' => 'DRY-DEL-01',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 300,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-DEL-UI-01',
        'quantity' => 50,
        'received_quantity' => 50,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-12-01',
    ]);

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertSee('confirmDelete(' . $stock->id . ',', false);
    $response->assertSee('showDeleteModal', false);
    $response->assertSee('Delete Stock Batch', false);
    $response->assertSee('Are you sure you want to delete this stock batch?', false);
    $response->assertSee('PRODUCT & BATCH', false);
    $response->assertSee("deleteStockProductName + ' — ' + deleteStockBatchNumber", false);
    $response->assertSee('This action cannot be undone and will permanently remove this stock batch from your inventory.', false);
    $response->assertSee('Delete Batch', false);
    $response->assertSee('submitDeleteStock()', false);
    $response->assertSee('deletedStockIds', false);
});

test('authenticated user can delete stock batch via ajax and receives json response', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);
    $product = Product::create([
        'name' => 'Delete Ajax Test Milk',
        'sku' => 'DRY-DEL-02',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 300,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-TO-DELETE-01',
        'quantity' => 100,
        'received_quantity' => 100,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-12-01',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('stocks.destroy', $stock));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Stock deleted successfully',
            'batch_number' => 'BATCH-TO-DELETE-01',
        ]);

    $this->assertDatabaseMissing('stocks', [
        'id' => $stock->id,
    ]);
});

test('unauthenticated user cannot delete stock batch', function () {
    $category = Category::firstOrCreate(['name' => 'Drinks']);
    $product = Product::create([
        'name' => 'Guest Delete Test Milk',
        'sku' => 'DRY-DEL-03',
        'category_id' => $category->id,
        'capacity' => '1L',
        'unit_price' => 300,
        'status' => 'Active',
    ]);

    $stock = Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-GUEST-DEL',
        'quantity' => 100,
        'received_quantity' => 100,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-12-01',
    ]);

    $response = $this->deleteJson(route('stocks.destroy', $stock));

    $response->assertUnauthorized();

    $this->assertDatabaseHas('stocks', [
        'id' => $stock->id,
    ]);
});

