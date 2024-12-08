<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ProjectAccountSession extends Model
{
    protected $fillable = [
        'project_account_id',
        'reference',
        'session_id',
        'auth_country_code',
        'authenticated_at',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ProjectAccount::class, 'project_account_id', 'id');
    }

    public function project(): HasOneThrough
    {
        return $this->hasOneThrough(Project::class, ProjectAccount::class, 'id', 'id', 'project_account_id', 'project_id');
    }

    public function stats(): HasOne
    {
        return $this->hasOne(ProjectAccountStats::class, 'project_account_id', 'project_account_id');
    }
}
