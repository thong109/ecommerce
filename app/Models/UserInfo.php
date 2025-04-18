<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'address',
        'avatar',
        'location',
        'description',
        'user_id'
    ];

    protected $hidden = [
        'avatar_old',
        // 'avatar_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
