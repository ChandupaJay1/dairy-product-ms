<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;

test('authenticated user can view products page with catalog and categories', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Products Directory');
    $response->assertSee('Drinks');
});

test('authenticated user can store a new product linked to a category', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $response = $this->actingAs($user)->post(route('products.store'), [
        'name' => 'Farm Fresh Full Cream Milk',
        'category_id' => $category->id,
        'unit_price' => 380.00,
        'capacity_value' => 1,
        'capacity_unit' => 'l',
        'sku' => 'DRY-9999',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'name' => 'Farm Fresh Full Cream Milk',
        'category_id' => $category->id,
        'capacity' => '1 l',
        'unit_price' => 380.00,
        'sku' => 'DRY-9999',
        'status' => 'Active',
    ]);

    $product = Product::where('sku', 'DRY-9999')->first();
    expect($product->category)->toBeInstanceOf(Category::class);
    expect($product->category->id)->toBe($category->id);
    expect($category->products()->pluck('id'))->toContain($product->id);
});

test('product creation validates required category_id and other fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('products.store'), []);

    $response->assertSessionHasErrors(['name', 'category_id', 'unit_price', 'capacity_value', 'capacity_unit']);
});

test('product creation auto-generates sku when sku is omitted or duplicate', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Butter']);

    $existing = Product::create([
        'name' => 'Existing Butter',
        'sku' => 'DRY-0001',
        'category_id' => $category->id,
        'capacity' => '200 g',
        'unit_price' => 500,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->post(route('products.store'), [
        'name' => 'Another Butter',
        'category_id' => $category->id,
        'unit_price' => 600,
        'capacity_value' => 250,
        'capacity_unit' => 'g',
        'sku' => 'DRY-0001', // duplicate sku provided
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('success');

    // Should create with an auto-generated unique SKU instead of failing
    $created = Product::where('name', 'Another Butter')->first();
    expect($created)->not->toBeNull();
    expect($created->sku)->not->toBe('DRY-0001');
    expect($created->sku)->toStartWith('DRY-');
    expect($created->category_id)->toBe($category->id);
});

test('category hasMany products and product belongsTo category relations work correctly', function () {
    $category = Category::create(['name' => 'TestCategory']);
    $product = Product::create([
        'name' => 'Test Yogurt',
        'sku' => 'DRY-TEST-01',
        'category_id' => $category->id,
        'capacity' => '100 ml',
        'unit_price' => 99.00,
        'status' => 'Active',
    ]);

    expect($product->category->name)->toBe('TestCategory');
    expect($category->products->pluck('id'))->toContain($product->id);
});

test('validation error messages render when submitting invalid product', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.store'), []);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHasErrors(['name', 'category_id', 'unit_price', 'capacity_value', 'capacity_unit']);
});

test('ajax request returns 422 json with validation errors', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('products.store'), []);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors'])
        ->assertJsonValidationErrors(['name', 'category_id', 'unit_price', 'capacity_value', 'capacity_unit']);
});

test('ajax request returns 201 json on successful product store', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $response = $this->actingAs($user)
        ->postJson(route('products.store'), [
            'name' => 'Almond Milk',
            'category_id' => $category->id,
            'unit_price' => 450.00,
            'capacity_value' => 500,
            'capacity_unit' => 'ml',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Almond Milk',
        'category_id' => $category->id,
    ]);
});

test('authenticated user can update an existing product', function () {
    $user = User::factory()->create();
    $drinksCategory = Category::firstOrCreate(['name' => 'Drinks']);
    $cheeseCategory = Category::firstOrCreate(['name' => 'Cheese']);

    $product = Product::create([
        'name' => 'Original Milk',
        'sku' => 'DRY-0088',
        'category_id' => $drinksCategory->id,
        'capacity' => '500 ml',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->put(route('products.update', $product), [
        'name' => 'Updated Cheddar Block',
        'category_id' => $cheeseCategory->id,
        'unit_price' => 750.50,
        'capacity_value' => 1,
        'capacity_unit' => 'kg',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Cheddar Block',
        'category_id' => $cheeseCategory->id,
        'capacity' => '1 kg',
        'unit_price' => 750.50,
        'sku' => 'DRY-0088', // SKU remains unchanged
    ]);
});

test('ajax request returns 200 json on successful product update', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'Old Milk',
        'sku' => 'DRY-0089',
        'category_id' => $category->id,
        'capacity' => '1 l',
        'unit_price' => 300.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->putJson(route('products.update', $product), [
        'name' => 'New Flavored Milk',
        'category_id' => $category->id,
        'unit_price' => 350.00,
        'capacity_value' => 750,
        'capacity_unit' => 'ml',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Flavored Milk',
        'capacity' => '750 ml',
        'unit_price' => 350.00,
    ]);
});

test('product update validates required fields', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'Milk',
        'sku' => 'DRY-0090',
        'category_id' => $category->id,
        'capacity' => '1 l',
        'unit_price' => 300.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->put(route('products.update', $product), []);

    $response->assertSessionHasErrors(['name', 'category_id', 'unit_price', 'capacity_value', 'capacity_unit']);
});

test('authenticated user can delete an existing product', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'To Be Deleted Milk',
        'sku' => 'DRY-0099',
        'category_id' => $category->id,
        'capacity' => '1 l',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('ajax request returns 200 json on successful product delete', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'To Be Deleted Ajax Milk',
        'sku' => 'DRY-0100',
        'category_id' => $category->id,
        'capacity' => '1 l',
        'unit_price' => 200.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('products.destroy', $product));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('sweetalert2 script and toast trigger render when session has success message', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Dairy product added successfully!'])
        ->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('sweetalert2@11');
    $response->assertSee('window.showSuccessToast');
    $response->assertSee('Dairy product added successfully!');
    $response->assertSee("window.fireSweetToast('success'", false);
    $response->assertSee('toast: true', false);
    $response->assertSee("position: 'top-end'", false);
    $response->assertSee('showConfirmButton: false', false);
    // Verify old HTML component is not present
    $response->assertDontSee('showToast: true');
});

test('sweetalert2 script and toast trigger render when session has error message', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['error' => 'Failed to process product operation!'])
        ->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('sweetalert2@11');
    $response->assertSee('window.showErrorToast');
    $response->assertSee('Failed to process product operation!');
    $response->assertSee("window.fireSweetToast('error'", false);
    $response->assertSee('toast: true', false);
    $response->assertSee("position: 'top-end'", false);
    $response->assertSee('showConfirmButton: false', false);
});

test('x-cloak css rule and attributes are present on modals to prevent fouc', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('[x-cloak]', false);
    $response->assertSee('display: none !important', false);
    $response->assertSee('x-show="showAddModal"', false);
    $response->assertSee('x-show="showEditModal"', false);
    $response->assertSee('x-show="showDeleteModal"', false);
});

test('sweetalert2 tailwind preflight isolation rules are present in layout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('.swal2-container div,', false);
    $response->assertSee('.swal2-container svg', false);
    $response->assertSee('box-sizing: content-box !important;', false);
    $response->assertSee('.swal2-success-ring', false);
    $response->assertSee('.swal2-success-line-tip', false);
    $response->assertSee('.swal2-success-line-long', false);
});

test('delete modal product name does not use font-mono styling', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('x-text="deleteProductName"', false);
    $response->assertDontSee('font-mono" x-text="deleteProductName"', false);
});

test('cannot delete product with active stock records via ajax', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'Protected Fresh Milk',
        'sku' => 'DRY-PRT-01',
        'category_id' => $category->id,
        'capacity' => '1 Liter',
        'unit_price' => 350.00,
        'status' => 'Active',
    ]);

    // Create associated stock record
    Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-PRT-001',
        'quantity' => 100,
        'received_quantity' => 100,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-12-01',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('products.destroy', $product));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Cannot delete this product because it has active stock records in the system.',
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});

test('cannot delete product with active stock records via standard request', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Drinks']);

    $product = Product::create([
        'name' => 'Protected Whole Milk',
        'sku' => 'DRY-PRT-02',
        'category_id' => $category->id,
        'capacity' => '500 ml',
        'unit_price' => 180.00,
        'status' => 'Active',
    ]);

    Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-PRT-002',
        'quantity' => 50,
        'received_quantity' => 50,
        'manufacture_date' => '2026-11-01',
        'issued_date' => '2026-11-05',
        'expiry_date' => '2026-12-01',
    ]);

    $response = $this->actingAs($user)->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('error', 'Cannot delete this product because it has active stock records in the system.');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});
