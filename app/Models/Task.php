<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
            'title',
            'description',
            'status_id',
            'project_id',
            'created_by'
        ];
        protected $attributes = [
            'status_id' => TaskStatus::PENDING_ASSIGNMENT
        ];
        public function project()
        {
            return $this->belongsTo(Project::class);
        }
        public function status()
        {
            return $this->belongsTo(TaskStatus::class);
        }
        public function createdBy()
        {
            return $this->belongsTo(User::class, 'created_by');
        }
        public function assignedUsers()
        {
            return $this->belongsToMany(User::class, 'task_user')
                ->withTimestamps();
        }
        public function scopeAssignedToUser($query, $userId)
        {
            return $query->whereHas('assignedUsers', function($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }
        public function scopeForProject($query, $projectId)
        {
            return $query->where('project_id', $projectId);
        }

        public function isAssignedTo($userId)
        {
            return $this->assignedUsers()->where('users.id', $userId)->exists();
        }
}
