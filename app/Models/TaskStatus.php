<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    use HasFactory;

    protected $table = 'task_statuses'; // Especificar nombre de tabla

    // Constantes para estados
    const PENDING_ASSIGNMENT = 1;
    const IN_PROGRESS = 2;
    const IN_TESTING = 3;
    const BUG = 4;
    const FINISHED = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_name',
        'formatted_created_at'
    ];

    /**
     * Relationship with Tasks
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id');
    }

    /**
     * Scope for active statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para tareas pendientes de asignación
     */
    public function scopePendingAssignment($query)
    {
        return $query->where('id', self::PENDING_ASSIGNMENT);
    }

    /**
     * Get the formatted name with status color (for UI)
     *
     * @return string
     */
    public function getFormattedNameAttribute()
    {
        return "<span style='color: {$this->color}'>{$this->name}</span>";
    }

    /**
     * Get the formatted created_at date
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at?? now();
        return $dateToFormat->format('d/m/Y H:i:s');
    }

    /**
     * Check if status is final (cannot be changed)
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->id === self::FINISHED;
    }

    /**
     * Check if task can be assigned in current status
     *
     * @return bool
     */
    public function allowsAssignment()
    {
        return $this->id === self::PENDING_ASSIGNMENT || $this->id === self::BUG;
    }

    /**
     * Check if status allows task modification
     *
     * @return bool
     */
    public function allowsModification()
    {
        return !$this->isFinal();
    }
}
