<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with product counts.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('name')) {
            $request->merge([
                'name' => trim((string) $request->name),
            ]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'bail',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $trimmed = trim((string) $value);
                    if ($trimmed === '') {
                        return;
                    }
                    $exists = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->exists();
                    if ($exists) {
                        $fail('This category already exists.');
                    }
                },
            ],
        ], [
            'name.required' => 'The category name field is required.',
            'name.max' => 'The category name may not be greater than 50 characters.',
        ], [
            'name' => 'category name',
        ]);

        $name = trim($validated['name']);

        $category = Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        $category->loadCount('products');

        return response()->json([
            'success' => true,
            'message' => 'Category added successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this category because products are linked to it.',
            ], 422);
        }

        $categoryName = $category->name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Category '{$categoryName}' deleted successfully.",
            'category_id' => $category->id,
        ]);
    }
}
