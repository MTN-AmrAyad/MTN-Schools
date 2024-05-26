<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id', 'round_name',
        'round_desc', 'round_cover',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }
}
