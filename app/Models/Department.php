<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'courses_number',
        'status',
    ];

    protected $casts = [
        'courses_number' => 'integer',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function students()
    {
        return $this->hasMany(User::class)->where('role', 'student');
    }

    public function faculty()
    {
        return $this->hasMany(User::class)->where('role', 'faculty');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active';
    }
}
