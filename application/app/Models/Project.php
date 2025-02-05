<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'public_api_key',
        'private_api_key',
        'geo_blocked_countries',
        'session_valid_for_seconds',
    ];

    protected $hidden = [
        'private_api_key',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(ProjectAccount::class);
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectAccountSession::class, ProjectAccount::class, 'project_id', 'project_account_id', 'id', 'id');
    }

    protected function privateApiKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }
}
