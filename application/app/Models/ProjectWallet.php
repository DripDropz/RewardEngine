<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectWallet extends Model
{
    protected $fillable = [
        'project_id',
        'stake_key_hex',
        'stake_key_address',
        'auth_nonce',
        'auth_issued',
        'auth_expiration',
    ];

    protected $casts = [
        'auth_issued' => 'datetime:Y-m-d H:i:s',
        'auth_expiration' => 'datetime:Y-m-d H:i:s',
    ];
}
