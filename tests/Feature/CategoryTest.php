<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('categories table has expected columns including slug', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();
    expect(Schema::hasColumns('categories', [
        'id',
        'name',
        'slug',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('category model auto generates slug when created without one', function () {
    $category = Category::create([
        'name' => 'Artisanal Ice Cream',
    ]);

    expect($category->slug)->toBe('artisanal-ice-cream');
});

test('category index endpoint returns categories with product count', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Index Test Category']);

    Product::create([
        'name' => 'Index Milk',
        'sku' => 'IND-0001',
        'category_id' => $category->id,
        'capacity' => '500ml',
        'unit_price' => 150.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->getJson(route('categories.index'));

    $response->assertOk();
    $catData = collect($response->json())->firstWhere('id', $category->id);
    expect($catData)->not->toBeNull();
    expect($catData['products_count'])->toBe(1);
});

test('category store validates name is required, max 50, and unique', function () {
    $user = User::factory()->create();
    Category::firstOrCreate(['name' => 'Existing Category']);

    // Required
    $response = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => '',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
    expect($response->json('errors.name.0'))->toBe('The category name field is required.');

    // Max 50
    $response = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => str_repeat('a', 51),
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // Unique (case-insensitive and trimmed)
    $response = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => 'Existing Category',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
    expect($response->json('errors.name.0'))->toBe('This category already exists.');

    // Case-insensitive duplicate check ('existing category')
    $responseCase = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => 'existing category',
    ]);
    $responseCase->assertStatus(422);
    $responseCase->assertJsonValidationErrors(['name']);
    expect($responseCase->json('errors.name.0'))->toBe('This category already exists.');

    // Whitespace + case duplicate check ('  EXISTING CATEGORY  ')
    $responseSpace = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => '  EXISTING CATEGORY  ',
    ]);
    $responseSpace->assertStatus(422);
    $responseSpace->assertJsonValidationErrors(['name']);
    expect($responseSpace->json('errors.name.0'))->toBe('This category already exists.');
});

test('category store successfully creates category and returns json response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('categories.store'), [
        'name' => 'Probiotic Kefir',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => 'Category added successfully.',
        'category' => [
            'name' => 'Probiotic Kefir',
            'slug' => 'probiotic-kefir',
            'products_count' => 0,
        ],
    ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Probiotic Kefir',
        'slug' => 'probiotic-kefir',
    ]);
});

test('category delete is prevented when products are linked to it', function () {
    $user = User::factory()->create();
    $category = Category::firstOrCreate(['name' => 'Protected Category']);

    Product::create([
        'name' => 'Linked Yogurt',
        'sku' => 'LNK-0001',
        'category_id' => $category->id,
        'capacity' => '200g',
        'unit_price' => 100.00,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('categories.destroy', $category));

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Cannot delete this category because products are linked to it.',
    ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
    ]);
});

test('category delete successfully removes unused category', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Unused Category',
        'slug' => 'unused-category',
    ]);

    $response = $this->actingAs($user)->deleteJson(route('categories.destroy', $category));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'category_id' => $category->id,
    ]);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('products directory renders manage categories button and modal elements', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Manage Categories', false);
    $response->assertSee('showCategoryModal', false);
    $response->assertSee('openCategoryModal', false);
    $response->assertSee('submitAddCategory', false);
    $response->assertSee('deleteCategory', false);
    $response->assertSee('Existing Categories', false);
    $response->assertSee('Category Name', false);
});

