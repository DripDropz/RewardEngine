<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'public_api_key',
        'private_api_key',
        'geo_blocked_countries',
    ];

    protected $hidden = [
        'private_api_key',
    ];
}
