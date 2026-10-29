<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    public $timestamps = false;
    protected $table = 'stock_returns';

    protected $fillable = [
        'return_number',
        'shop_id',
        'dispatch_id',
        'sales_rep_id',
        'flavor_name',
        'batch_number',
        'expiry_date',
        'quantity_cups',
        'unit_price',
        'total_value',
        'return_reason',
        'settlement_action',
        'notes',
        'created_at'
    ];
}
