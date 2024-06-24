<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $fillable = [
        'email', 'password',
    ];

    public function userMeta()
    {
        return $this->hasOne(UserMeta::class);
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }
    public function savedVideos()
    {
        return $this->belongsToMany(Video::class, 'saved_videos')->withTimestamps();
    }
    public function likedVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_likes');
    }
    public function acceptPaymentGroups()
    {
        return $this->hasMany(AcceptPaymentGroup::class);
    }
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }
}
