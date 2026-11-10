<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\Request;

/**
 * More than one PC can run the worker. The main one takes the work; a backup one (the owner's laptop) takes
 * new jobs and trials only while the main one has not been heard from for a few minutes. Each worker says
 * its role in the X-Worker-Role header; without it, a worker counts as main.
 */
class WorkerRoles
{
    public const MAIN = 'main';

    public const BACKUP = 'backup';

    private const MAIN_SILENT_SECONDS = 300;

    public function __construct(private readonly WorkerHeartbeat $heartbeat) {}

    public function noteRequest(Request $request): void
    {
        if ($this->roleOf($request) === self::MAIN) {
            $this->heartbeat->beat(self::MAIN);
        }
    }

    /** A backup worker stays idle while the main one is alive. */
    public function mayTakeNewWork(Request $request): bool
    {
        return $this->roleOf($request) === self::MAIN || ! $this->heartbeat->seenWithin(self::MAIN, self::MAIN_SILENT_SECONDS);
    }

    private function roleOf(Request $request): string
    {
        return $request->header('X-Worker-Role') === self::BACKUP ? self::BACKUP : self::MAIN;
    }
}
