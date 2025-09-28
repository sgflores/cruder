<?php

namespace SgFlores\Cruder\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use SgFlores\Cruder\Tests\Factories\UserFactory;

class User extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
