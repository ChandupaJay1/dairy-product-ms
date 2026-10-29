<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrateRecord extends Model
{
    public $timestamps = false;
    protected $table = 'crate_records';

    protected $fillable = [
        'dispatch_id',
        'shop_id',
        'crates_issued',
        'crates_collected',
        'curd_pots_issued',
        'curd_pots_collected',
        'pot_deposit_fee',
        'notes',
        'created_at'
    ];
}
