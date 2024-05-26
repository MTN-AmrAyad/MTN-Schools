<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;
    protected $fillable = [
        'round_id', 'chapter_name',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
