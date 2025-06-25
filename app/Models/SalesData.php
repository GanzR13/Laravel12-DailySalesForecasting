<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesData extends Model
{

    protected $table = 'sales_data';

    public $timestamps = true;

    protected $fillable = [
        'store',
        'dept',
        'date',
        'daily_sales',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
        'daily_sales' => 'decimal:2',
        'store' => 'integer',
        'dept' => 'integer',
        'updated_at' => 'datetime',
    ];

}
