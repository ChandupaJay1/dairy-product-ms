<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display the products directory with pre-generated unique SKU, category list, and product catalog.
     */
    public function index(): View
    {
        $products = Product::with('category')->latest('id')->get();
        $categories = Category::withCount('products')->orderBy('name')->get();
        $nextSku = $this->generateSku();

        return view('products.index', compact('products', 'categories', 'nextSku'));
    }

    /**
     * Generate a unique SKU number based on the last created product ID.
     * Formats with a 'DRY-' prefix and 4-digit zero-padded number (e.g., DRY-0001).
     */
    public function generateSku(string $prefix = 'DRY-'): string
    {
        $lastProduct = Product::latest('id')->first();
        $nextId = $lastProduct ? ($lastProduct->id + 1) : 1;

        do {
            $sku = $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * JSON endpoint to fetch an auto-generated unique SKU dynamically.
     */
    public function getNextSku(): JsonResponse
    {
        return response()->json([
            'sku' => $this->generateSku(),
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|bail|string|max:255',
            'category_id' => 'required|bail|exists:categories,id',
            'unit_price' => 'required|bail|numeric|min:0',
            'capacity_value' => 'required|bail|numeric|min:0',
            'capacity_unit' => 'required|bail|string|in:g,kg,ml,l',
            'sku' => 'nullable|string|max:50',
        ], [], [
            'category_id' => 'category',
            'capacity_value' => 'capacity',
            'unit_price' => 'unit price',
        ]);

        // Ensure a unique SKU (fallback to generateSku if empty or duplicate)
        $sku = !empty($validated['sku']) ? $validated['sku'] : $this->generateSku();
        if (Product::where('sku', $sku)->exists()) {
            $sku = $this->generateSku();
        }

        // Combine capacity value and unit
        $capacity = $validated['capacity_value'] . ' ' . $validated['capacity_unit'];

        $product = Product::create([
            'name' => $validated['name'],
            'sku' => $sku,
            'category_id' => $validated['category_id'],
            'capacity' => $capacity,
            'unit_price' => $validated['unit_price'],
            'status' => 'Active',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', 'Product "' . $product->name . '" (' . $product->sku . ') was created successfully!');

            return response()->json([
                'success' => true,
                'message' => 'Product "' . $product->name . '" (' . $product->sku . ') was created successfully!',
                'product' => $product->load('category'),
                'redirect' => route('products.index'),
            ], 201);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product "' . $product->name . '" (' . $product->sku . ') was created successfully!');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|bail|string|max:255',
            'category_id' => 'required|bail|exists:categories,id',
            'unit_price' => 'required|bail|numeric|min:0',
            'capacity_value' => 'required|bail|numeric|min:0',
            'capacity_unit' => 'required|bail|string|in:g,kg,ml,l',
        ], [], [
            'category_id' => 'category',
            'capacity_value' => 'capacity',
            'unit_price' => 'unit price',
        ]);

        $capacity = $validated['capacity_value'] . ' ' . $validated['capacity_unit'];

        $product->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'capacity' => $capacity,
            'unit_price' => $validated['unit_price'],
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', 'Product "' . $product->name . '" was updated successfully!');

            return response()->json([
                'success' => true,
                'message' => 'Product "' . $product->name . '" was updated successfully!',
                'product' => $product->load('category'),
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product "' . $product->name . '" was updated successfully!');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse|RedirectResponse
    {
        if ($product->stocks()->exists()) {
            $errorMessage = 'Cannot delete this product because it has active stock records in the system.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()->route('products.index')
                ->with('error', $errorMessage);
        }

        $productName = $product->name;
        $product->delete();

        if (request()->expectsJson() || request()->ajax()) {
            session()->flash('success', 'Product "' . $productName . '" was deleted successfully!');

            return response()->json([
                'success' => true,
                'message' => 'Product "' . $productName . '" was deleted successfully!',
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product "' . $productName . '" was deleted successfully!');
    }
}
