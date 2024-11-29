<?php

namespace App\Jobs;

use App\Models\EventData;
use App\Models\ProjectAccountSessionEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class HydraDoomEventParserJob implements ShouldQueue
{
    use Queueable;

    const TYPE_GLOBAL = 'global';
    const TYPE_NEW_GAME = 'new_game';
    const TYPE_PLAYER_JOINED = 'player_joined';
    const TYPE_GAME_FINISHED = 'game_finished';
    const TYPE_KILL = 'kill';
    const TYPE_DEATH = 'death';
    const TYPE_SUICIDE = 'suicide';

    private EventData $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(EventData $eventData)
    {
        $this->eventData = $eventData;
    }

    /**
     * Determine number of times the job may be attempted.
     *
     * @return int
     */
    public function tries(): int
    {
        return 10;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return 30;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->eventData->data['type']) || empty($this->eventData->data['game_id'])) {
            return;
        }

        switch ($this->eventData->data['type']) {
            case self::TYPE_GLOBAL:
                $this->processGlobalEvent();
                break;
            case self::TYPE_NEW_GAME:
                $this->processNewGameEvent();
                break;
            case self::TYPE_PLAYER_JOINED:
                $this->processPlayerJoinedEvent();
                break;
            case self::TYPE_GAME_FINISHED:
                $this->processGameFinishedEvent();
                break;
            case self::TYPE_KILL:
                $this->processKillEvent();
                break;
            default:
                echo sprintf('Unknown Event Type: %s', $this->eventData->data['type']);
                break;
        }
    }

    private function processGlobalEvent(): void
    {
        Cache::put(
            sprintf('project-%d:global-stats', $this->eventData->project_id),
            $this->eventData->data['stats'],
        );
    }

    private function processNewGameEvent(): void
    {
        $this->recordProjectAccountSessionEvent([
            'project_id' => $this->eventData->project_id,
            'reference' => null,
            'event_id' => $this->eventData->event_id,
            'event_type' => self::TYPE_NEW_GAME,
            'event_timestamp' => $this->eventData->timestamp,
            'game_id' => $this->eventData->data['game_id'],
            'target_reference' => null,
        ]);
    }

    private function processPlayerJoinedEvent(): void
    {
        if (empty($this->eventData->data['key'])) {
            return;
        }

        $this->recordProjectAccountSessionEvent([
            'project_id' => $this->eventData->project_id,
            'reference' => $this->eventData->data['key'],
            'event_id' => $this->eventData->event_id,
            'event_type' => self::TYPE_PLAYER_JOINED,
            'event_timestamp' => $this->eventData->timestamp,
            'game_id' => $this->eventData->data['game_id'],
            'target_reference' => null,
        ]);
    }

    private function processKillEvent(): void
    {
        $killerReference = $this->eventData->data['killer'];
        $victimReference = $this->eventData->data['victim'];

        if (empty($killerReference) || empty($victimReference)) {
            return;
        }

        if ($killerReference === $victimReference) {

            $this->recordProjectAccountSessionEvent([
                'project_id' => $this->eventData->project_id,
                'reference' => $killerReference,
                'event_id' => $this->eventData->event_id,
                'event_type' => self::TYPE_SUICIDE,
                'event_timestamp' => $this->eventData->timestamp,
                'game_id' => $this->eventData->data['game_id'],
                'target_reference' => null,
            ]);

        } else {

            $this->recordProjectAccountSessionEvent([
                'project_id' => $this->eventData->project_id,
                'reference' => $killerReference,
                'event_id' => $this->eventData->event_id,
                'event_type' => self::TYPE_KILL,
                'event_timestamp' => $this->eventData->timestamp,
                'game_id' => $this->eventData->data['game_id'],
                'target_reference' => $victimReference,
            ]);

            $this->recordProjectAccountSessionEvent([
                'project_id' => $this->eventData->project_id,
                'reference' => $victimReference,
                'event_id' => $this->eventData->event_id,
                'event_type' => self::TYPE_DEATH,
                'event_timestamp' => $this->eventData->timestamp,
                'game_id' => $this->eventData->data['game_id'],
                'target_reference' => $killerReference,
            ]);

        }
    }

    private function processGameFinishedEvent(): void
    {
        $allJoinedPlayers = ProjectAccountSessionEvent::query()
            ->where('game_id', $this->eventData->data['game_id'])
            ->where('event_type', self::TYPE_PLAYER_JOINED)
            ->select('reference');

        foreach ($allJoinedPlayers as $joinedPlayer) {
            try {
                ProjectAccountSessionEvent::create([
                    'project_id' => $this->eventData->project_id,
                    'reference' => $joinedPlayer,
                    'event_id' => $this->eventData->event_id,
                    'event_type' => self::TYPE_GAME_FINISHED,
                    'event_timestamp' => $this->eventData->timestamp,
                    'game_id' => $this->eventData->data['game_id'],
                    'target_reference' => null,
                ]);
            } catch (UniqueConstraintViolationException) {}
        }
    }

    private function recordProjectAccountSessionEvent(array $payload): void
    {
        try {
            ProjectAccountSessionEvent::create($payload);
            if (!empty($payload['reference'])) {
                dispatch(new HydraDoomAccountStatsJob($payload['project_id'], $payload['reference']));
            }
        } catch (UniqueConstraintViolationException) {}
    }
}
