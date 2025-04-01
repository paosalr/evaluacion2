<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Task;
use App\Models\ProjectStatus;

class Project extends Model
{
    protected $table = 'projects';
    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function developers()
    {
        return $this->belongsToMany(User::class)
            ->whereHas('role', function ($query) {
                $query->where('name', 'Desarrollador');
            });
    }
    public function status()
    {
        return $this->belongsTo(ProjectStatus::class);
    }
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }
    public function userCanView($user)
    {
        return $user->role->name === 'Planeación' ||
            $this->assignedUsers->contains($user->id);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->status_id)) {
                $project->status_id = ProjectStatus::PLANNING;
            }
        });
    }
}
