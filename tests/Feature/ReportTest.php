<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;

test('guest cannot download inventory pdf report', function () {
    $response = $this->get(route('reports.inventory-pdf'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can stream default full inventory pdf report', function () {
    $user = User::factory()->create();

    $category = Category::firstOrCreate(['name' => 'Report Drinks']);
    $product = Product::create([
        'name' => 'Full Cream Fresh Milk',
        'sku' => 'RPT-0001',
        'category_id' => $category->id,
        'capacity' => '1000ml',
        'unit_price' => 450.00,
        'status' => 'Active',
    ]);

    Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-RPT-01',
        'quantity' => 120,
        'received_quantity' => 120,
        'manufacture_date' => now()->subDays(2)->toDateString(),
        'expiry_date' => now()->addDays(14)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('reports.inventory-pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->not->toBeEmpty();
    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});

test('supports filtering reports by type parameter', function (string $type) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.inventory-pdf', ['type' => $type]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->not->toBeEmpty();
    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
})->with(['all', 'products', 'stocks', 'low_stock', 'expiring_soon', 'invalid_fallback']);

test('low stock and expiring soon batches are accurately evaluated for the pdf report', function () {
    $user = User::factory()->create();

    $category = Category::firstOrCreate(['name' => 'Report Curd']);
    $product = Product::create([
        'name' => 'Clay Pot Buffalo Curd',
        'sku' => 'RPT-0002',
        'category_id' => $category->id,
        'capacity' => '900g',
        'unit_price' => 600.00,
        'status' => 'Active',
    ]);

    // Low stock batch (<= 50)
    Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-LOW-01',
        'quantity' => 15,
        'received_quantity' => 100,
        'manufacture_date' => now()->subDays(5)->toDateString(),
        'expiry_date' => now()->addDays(20)->toDateString(),
    ]);

    // Expiring soon batch (<= 7 days)
    Stock::create([
        'product_id' => $product->id,
        'batch_number' => 'BATCH-EXP-01',
        'quantity' => 80,
        'received_quantity' => 80,
        'manufacture_date' => now()->subDays(10)->toDateString(),
        'expiry_date' => now()->addDays(3)->toDateString(),
    ]);

    $responseLow = $this->actingAs($user)->get(route('reports.inventory-pdf', ['type' => 'low_stock']));
    $responseLow->assertOk();
    $responseLow->assertHeader('content-type', 'application/pdf');

    $responseExp = $this->actingAs($user)->get(route('reports.inventory-pdf', ['type' => 'expiring_soon']));
    $responseExp->assertOk();
    $responseExp->assertHeader('content-type', 'application/pdf');
});

test('stocks management page renders export pdf report button and modal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stocks.index'));

    $response->assertOk();
    $response->assertSee('Export PDF Report', false);
    $response->assertSee('showExportModal', false);
    $response->assertSee('openExportModal', false);
    $response->assertSee('downloadExportPdf', false);
    $response->assertSee('Full Inventory Audit Report', false);
    $response->assertSee('Expiring Soon Batches Only', false);
});

test('products directory page renders export pdf report button and modal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Export PDF Report', false);
    $response->assertSee('showExportModal', false);
    $response->assertSee('openExportModal', false);
    $response->assertSee('downloadExportPdf', false);
    $response->assertSee('Full Inventory Audit Report', false);
    $response->assertSee('Expiring Soon Batches Only', false);
});
