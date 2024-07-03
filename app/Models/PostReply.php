<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReply extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'comment_id', 'reply'
    ];

    public function comment()
    {
        return $this->belongsTo(PostComment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
