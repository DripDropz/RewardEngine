<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ProjectAccountStats extends Model
{
    protected $fillable = [
        'project_id',
        'project_account_id',
        'stats',
    ];

    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? json_decode($value, true) : null,
            set: fn (string|null $value) => $value ? json_encode($value) : null,
        );
    }
}
