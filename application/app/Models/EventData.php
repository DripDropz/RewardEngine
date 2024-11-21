<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class EventData extends Model
{
    protected $fillable = [
        'project_id',
        'event_id',
        'data',
        'timestamp',
    ];
}
