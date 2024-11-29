<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class ProjectAccountSessionEvent extends Model
{
    protected $fillable = [
        'project_id',
        'reference',
        'event_id',
        'event_type',
        'event_timestamp',
        'game_id',
        'target_reference',
    ];
}
