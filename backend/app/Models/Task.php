<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'title',
        'executor',
        'due_date',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed' => 'boolean',
        ];
    }
}
