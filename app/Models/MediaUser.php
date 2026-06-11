<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaUser extends Model
{
    protected $table = 'media_user';

    protected $fillable = [
        'user_id',
        'path',
        'original_name',
        'mime_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
