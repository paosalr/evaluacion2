<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'users';
    protected $fillable = [
        'name',
        'last_name_p',
        'last_name_m',
        'email',
        'password',
        'role_id',
        'registration_date',
        'is_active',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'registration_date' => 'datetime',
    ];

    protected $dates = [
        'registration_date',
    ];
    protected $appends = [
        'full_name',
        'formatted_registration_date'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->last_name_p} {$this->last_name_m}";
    }
    public function getFormattedRegistrationDateAttribute()
    {
        return $this->registration_date ? $this->registration_date->format('Y-m-d') : null;
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function projects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }
    public function tasks()
    {
        return $this->belongsToMany(Task::class)
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }
}

