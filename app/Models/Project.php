<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Task;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'creation_date',
        'status'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
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
}
