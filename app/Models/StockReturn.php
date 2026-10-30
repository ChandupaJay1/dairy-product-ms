<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    public $timestamps = false;
    protected $table = 'stock_returns';
    protected $guarded = [];
}
