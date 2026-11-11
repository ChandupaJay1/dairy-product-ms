<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity',
        'received_quantity',
        'expiry_date',
        'manufacture_date',
        'issued_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
        'manufacture_date' => 'date',
        'issued_date' => 'date',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    /**
     * Mutator to keep quantity and received_quantity in sync.
     */
    public function setReceivedQuantityAttribute($value): void
    {
        $this->attributes['received_quantity'] = (int) $value;
        if (! isset($this->attributes['quantity']) || $this->attributes['quantity'] === null) {
            $this->attributes['quantity'] = (int) $value;
        }
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = (int) $value;
        if (! isset($this->attributes['received_quantity']) || $this->attributes['received_quantity'] === null) {
            $this->attributes['received_quantity'] = (int) $value;
        }
    }

    /**
     * Mutator to ensure date columns are stored in standard Y-m-d format.
     */
    public function setManufactureDateAttribute($value): void
    {
        $this->attributes['manufacture_date'] = ! empty($value) ? substr((string) $value, 0, 10) : null;
    }

    public function setIssuedDateAttribute($value): void
    {
        $this->attributes['issued_date'] = ! empty($value) ? substr((string) $value, 0, 10) : null;
    }

    public function setExpiryDateAttribute($value): void
    {
        $this->attributes['expiry_date'] = ! empty($value) ? substr((string) $value, 0, 10) : null;
    }

    /**
     * Get the product that this stock batch belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the adjustment audit logs for this stock batch.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class)->latest('id');
    }
}
