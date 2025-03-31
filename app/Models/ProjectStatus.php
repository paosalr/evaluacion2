<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use HasFactory;
    const PLANNING = 1;
    const IN_DEVELOPMENT = 2;
    const PAUSED = 3;
    const CANCELLED = 4;
    const FINISHED = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name'
    ];
    public function projects()
    {
        return $this->hasMany(Project::class, 'status_id');
    }
    public function scopePlanning($query)
    {
        return $query->where('id', self::PLANNING);
    }
    public function scopeInDevelopment($query)
    {
        return $query->where('id', self::IN_DEVELOPMENT);
    }
    public function isPlanning()
    {
        return $this->id === self::PLANNING;
    }
    public function allowsModifications()
    {
        return in_array($this->id, [self::PLANNING, self::IN_DEVELOPMENT, self::PAUSED]);
    }
}
