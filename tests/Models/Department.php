<?php

namespace SgFlores\Cruder\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SgFlores\Cruder\Tests\Factories\DepartmentFactory;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return DepartmentFactory::new();
    }

    protected $table = 'test_departments';

    protected $fillable = [
        'name',
        'description',
        'code',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function createdBy(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function updatedBy(): HasMany
    {
        return $this->hasMany(User::class, 'updated_by');
    }

    public function deletedBy(): HasMany
    {
        return $this->hasMany(User::class, 'deleted_by');
    }
}
