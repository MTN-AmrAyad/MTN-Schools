<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id', 'video_name',
        'video_photo', 'video_link',
        'video_desc', 'author_name',
        'video_status',

    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_videos')->withTimestamps();
    }
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'video_likes');
    }
    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }
}
