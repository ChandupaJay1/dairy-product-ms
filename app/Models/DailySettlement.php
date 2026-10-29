<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySettlement extends Model
{
    public $timestamps = false;
    protected $table = 'daily_settlements';

    protected $fillable = [
        'settlement_number',
        'dispatch_id',
        'settlement_date',
        'cashier_name',
        'driver_name',
        'sales_rep_name',
        'total_invoiced_cash',
        'total_invoiced_credit',
        'physical_cash_handed',
        'cash_variance',
        'loaded_cups',
        'sold_cups',
        'returned_cups',
        'damaged_cups',
        'expected_stock_cups',
        'actual_stock_checked_in',
        'stock_variance',
        'crates_issued_total',
        'crates_returned_total',
        'crates_variance',
        'curd_pots_returned',
        'status',
        'variance_notes',
        'created_at'
    ];
}
