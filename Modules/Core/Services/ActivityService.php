<?php

namespace Modules\Core\Services;

use Modules\Core\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * ActivityService — centralized activity logging for the iSAAS ecosystem.
 *
 * Usage:
 *   ActivityService::log(
 *       event: 'invoice.paid',
 *       description: 'Invoice #1234 was paid by John Doe',
 *       subject: $invoice,
 *       actor: auth()->user(),
 *       workspace: 'erp',
 *       properties: ['amount' => 500, 'currency' => 'USD']
 *   );
 */
class ActivityService
{
    /**
     * Log a meaningful system activity.
     *
     * @param  string           $event        Dot-notation key (e.g. invoice.paid)
     * @param  string           $description  Human-readable sentence
     * @param  Model|null       $subject      The entity this activity is about
     * @param  User|null        $actor        Who triggered this (null = system)
     * @param  string|null      $workspace    erp | marketplace | freelance | booking | system
     * @param  array            $properties   Extra structured context
     */
    public static function log(
        string  $event,
        string  $description,
        ?Model  $subject   = null,
        ?User   $actor     = null,
        ?string $workspace = null,
        array   $properties = []
    ): ?ActivityEvent {
        try {
            return ActivityEvent::create([
                'user_id'      => $actor?->id ?? (auth()->check() ? auth()->id() : null),
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'event'        => $event,
                'description'  => $description,
                'properties'   => empty($properties) ? null : $properties,
                'workspace'    => $workspace,
            ]);
        } catch (Throwable $e) {
            // Never let activity logging crash the main request
            logger()->error('ActivityService::log failed', [
                'event'       => $event,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve a paginated feed for the global activity log page.
     */
    public static function feed(
        ?string $workspace = null,
        ?string $event     = null,
        ?int    $userId    = null,
        int     $perPage   = 25
    ) {
        $query = ActivityEvent::with('user')->recent();

        if ($workspace) {
            $query->forWorkspace($workspace);
        }

        if ($event) {
            $query->ofEvent($event);
        }

        if ($userId) {
            $query->forUser($userId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Retrieve the latest N activities for a specific subject (e.g. an invoice).
     */
    public static function forSubject(Model $subject, int $limit = 10)
    {
        return ActivityEvent::with('user')
            ->forSubject(get_class($subject), $subject->getKey())
            ->recent()
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve the latest N activities for a user's personal feed.
     */
    public static function forUser(int $userId, int $limit = 20)
    {
        return ActivityEvent::with('user')
            ->forUser($userId)
            ->recent()
            ->limit($limit)
            ->get();
    }
}
