<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'add',
        'edit',
        'delete',
        'view',
        'download',
    ];

    protected $casts = [
        'add' => 'boolean',
        'edit' => 'boolean',
        'delete' => 'boolean',
        'view' => 'boolean',
        'download' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
