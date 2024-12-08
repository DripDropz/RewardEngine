<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ProjectAccount extends Model
{
    protected $fillable = [
        'project_id',
        'auth_provider',
        'auth_provider_id',
        'auth_wallet',
        'auth_name',
        'auth_email',
        'auth_avatar',
        'generated_wallet_mnemonic',
        'generated_wallet_stake_address',
        'linked_wallet_stake_address',
        'linked_discord_id',
    ];

    protected $casts = [
        'auth_issued' => 'datetime:Y-m-d H:i:s',
        'auth_expiration' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'generated_wallet_mnemonic',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ProjectAccountSession::class);
    }

    public function sessionEvents(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectAccountSessionEvent::class, ProjectAccountSession::class, 'project_account_id', 'reference', 'id', 'reference');
    }

    protected function authName(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? decrypt($value) : null,
            set: fn (string|null $value) => $value ? encrypt($value) : null,
        );
    }

    protected function authEmail(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? decrypt($value) : null,
            set: fn (string|null $value) => $value ? encrypt($value) : null,
        );
    }

    protected function authAvatar(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? decrypt($value) : null,
            set: fn (string|null $value) => $value ? encrypt($value) : null,
        );
    }

    protected function generatedWalletMnemonic(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? decrypt($value) : null,
            set: fn (string|null $value) => $value ? encrypt($value) : null,
        );
    }

    protected function linkedDiscordId(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? decrypt($value) : null,
            set: fn (string|null $value) => $value ? encrypt($value) : null,
        );
    }
}
