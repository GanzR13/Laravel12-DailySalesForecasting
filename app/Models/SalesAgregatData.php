<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesAgregatData extends Model
{
    protected $table = 'sales_agregat';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'actual',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'actual' => 'float',
        'updated_at' => 'datetime',
    ];
}
