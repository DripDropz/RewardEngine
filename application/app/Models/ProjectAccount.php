<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAccount extends Model
{
    protected $fillable = [
        'project_id',
        'reference',
        'auth_provider',
        'auth_provider_id',
        'auth_name',
        'auth_email',
        'auth_avatar',
        'auth_country_code',
        'authenticated_at',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function authName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }

    protected function authEmail(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }

    protected function authAvatar(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }
}
