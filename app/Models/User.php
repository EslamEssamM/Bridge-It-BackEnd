<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable ,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'phone',
        'user_id',
        'avatar',
        'bio',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function files():MorphMany
    {
        return $this->morphMany(File::class,'filable');
    }
    public function templates():HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function UserTokens():HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    public function tasks():HasMany
    {
        return $this->hasMany(Task::class,'author_id');
    }

    public function assignedTasks():HasMany
    {
        return $this->hasMany(Task::class,'assigned_to');
    }
}
