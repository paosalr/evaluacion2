<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    const RH = 1;
    const DEVELOPER = 2;
    const PLANNING = 3;
    const TESTER = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected  $fillable = ['name'];
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
