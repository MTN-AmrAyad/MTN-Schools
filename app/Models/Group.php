<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_name', 'group_desc',
        'group_cover', 'group_role',
        'group_status'
    ];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }
}
