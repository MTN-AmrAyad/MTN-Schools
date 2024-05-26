<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id', 'video_name',
        'video_photo', 'video_link',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
