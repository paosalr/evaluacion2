<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // App\Models\Project.php

    public function developers()
    {
        return $this->belongsToMany(User::class)
            ->whereHas('role', function ($query) {
                $query->where('name', 'Desarrollador');
            });
    }
}
