<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id', 'title', 'start', 'end', 'allDay'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
