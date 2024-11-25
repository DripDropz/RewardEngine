<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ProjectWalletSession extends Model
{
    protected $fillable = [
        'project_wallet_id',
        'reference',
        'session_id',
        'auth_country_code',
        'authenticated_at',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ProjectWallet::class, 'project_wallet_id', 'id');
    }

    public function project(): HasOneThrough
    {
        return $this->hasOneThrough(Project::class, ProjectWallet::class, 'id', 'id', 'project_wallet_id', 'project_id');
    }
}
