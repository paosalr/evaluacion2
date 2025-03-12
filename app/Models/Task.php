<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
        protected $fillable = [
            'title',
            'description',
            'creation_date',
            'status',
            'project_id'
        ];

        public function project()
        {
            return $this->belongsTo(Project::class);
        }

        public function users()
        {
            return $this->belongsToMany(User::class);
        }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
