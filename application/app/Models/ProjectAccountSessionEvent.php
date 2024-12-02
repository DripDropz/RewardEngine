<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function eventData(): BelongsTo
    {
        return $this->belongsTo(EventData::class, 'event_id', 'event_id');
    }
}
