<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'country_code', 'phone_number',
        'cover_image', 'profile_image'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
