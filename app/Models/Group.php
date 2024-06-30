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
        'meetingNumber', 'meetingPassword',
        'price',
        'group_status' // for paid and free

    ];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function acceptPaymentGroups()
    {
        return $this->hasMany(AcceptPaymentGroup::class);
    }
    public function images()
    {
        return $this->hasMany(GroupImage::class);
    }
}
