<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentimenData extends Model
{
    protected $table = 'sentimen_data';

    public $timestamps = true;

    protected $fillable = [
        'review_text',
        'label_sentimen',
        'updated_at'
    ];
}
