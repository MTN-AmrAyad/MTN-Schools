<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'group_id', 'round_id', 'chapter_id', 'video_id', 'is_completed'
    ];
}
