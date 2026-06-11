<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class userAdmin extends Model
{
     protected $table = 'user_admins';
     protected $fillable = [
        'email'
    ];
}
