<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRep extends Model
{
    protected $table = 'sales_representatives';

    protected $fillable = [
        'user_id',
        'employee_code',
        'name',
        'phone',
        'assigned_route_sector',
        'commission_rate_percent',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
