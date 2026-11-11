<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'capacity',
        'unit_price',
        'status',
    ];

    /**
     * Get the category that this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the stock shipments for this product.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get product name alias (for product_name).
     */
    public function getProductNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    /**
     * Get SKU alias (for sku_number).
     */
    public function getSkuNumberAttribute(): string
    {
        return $this->attributes['sku'] ?? '';
    }

    /**
     * Auto-generate a unique SKU number.
     * Fetches the last product's ID, increments it, pads with zeros, and appends to prefix.
     */
    public static function generateUniqueSku(string $prefix = 'DRY-'): string
    {
        $lastProduct = static::latest('id')->first();
        $nextId = $lastProduct ? ($lastProduct->id + 1) : 1;

        do {
            $sku = $prefix.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }
}
