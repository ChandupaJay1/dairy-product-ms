<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Standardize Yoghurt / Yogurt variations
        $canonical = Category::whereRaw('LOWER(name) = ?', ['yoghurt'])->first()
            ?? Category::whereRaw('LOWER(name) = ?', ['yogurt'])->first();

        if ($canonical) {
            $duplicates = Category::where('id', '!=', $canonical->id)
                ->where(function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['yogurt'])
                        ->orWhereRaw('LOWER(name) = ?', ['yoghurt']);
                })
                ->get();

            foreach ($duplicates as $duplicate) {
                Product::where('category_id', $duplicate->id)->update(['category_id' => $canonical->id]);
                $duplicate->delete();
            }
        }

        // Generic deduplication: merge any case-insensitive duplicates
        $categories = Category::all();
        $grouped = $categories->groupBy(fn ($cat) => strtolower(trim($cat->name)));

        foreach ($grouped as $group) {
            if ($group->count() > 1) {
                $keep = $group->sortByDesc(fn ($cat) => $cat->products()->count())->first();
                foreach ($group as $cat) {
                    if ($cat->id !== $keep->id) {
                        Product::where('category_id', $cat->id)->update(['category_id' => $keep->id]);
                        $cat->delete();
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data cleanup migration
    }
};
