<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
    'sale_id',
    'partner_id',
    'client_number',
    'amount',
    'payment_method',
    'reference',
];
}
