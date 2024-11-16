<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(ProjectAccount::class);
    }
}
