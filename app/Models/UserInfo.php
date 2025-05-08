<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone',
        'address',
        'avatar',
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
