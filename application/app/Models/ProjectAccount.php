<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectAccount extends Model
{
    protected $fillable = [
        'project_id',
        'auth_provider',
        'auth_provider_id',
        'auth_name',
        'auth_email',
        'auth_avatar',
        'stake_key_address',
        'auth_nonce',
        'auth_issued',
        'auth_expiration',
    ];

    protected $casts = [
        'auth_issued' => 'datetime:Y-m-d H:i:s',
        'auth_expiration' => 'datetime:Y-m-d H:i:s',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ProjectAccountSession::class);
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
