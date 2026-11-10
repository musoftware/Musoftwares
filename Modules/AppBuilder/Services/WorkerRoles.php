<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * More than one PC can run the worker. The main one takes the work; a backup one (the owner's laptop) takes
 * new jobs and trials only while the main one has not been heard from for a few minutes. Each worker says
 * its role in the X-Worker-Role header; without it, a worker counts as main.
 */
class WorkerRoles
{
    public const MAIN = 'main';

    public const BACKUP = 'backup';

    private const MAIN_SEEN_KEY = 'builder:worker:main:last-seen';

    private const MAIN_SILENT_MINUTES = 5;

    public function noteRequest(Request $request): void
    {
        if ($this->roleOf($request) === self::MAIN) {
            Cache::put(self::MAIN_SEEN_KEY, now()->getTimestamp(), now()->addDay());
        }
    }

    /** A backup worker stays idle while the main one is alive. */
    public function mayTakeNewWork(Request $request): bool
    {
        if ($this->roleOf($request) === self::MAIN) {
            return true;
        }

        $lastSeen = (int) Cache::get(self::MAIN_SEEN_KEY, 0);

        return $lastSeen < now()->subMinutes(self::MAIN_SILENT_MINUTES)->getTimestamp();
    }

    private function roleOf(Request $request): string
    {
        return $request->header('X-Worker-Role') === self::BACKUP ? self::BACKUP : self::MAIN;
    }
}
