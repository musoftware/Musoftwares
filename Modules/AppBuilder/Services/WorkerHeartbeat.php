<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;

/** When the site last heard from a worker, in the database so it survives between requests. */
class WorkerHeartbeat
{
    /** A worker polls every few seconds; writing once in this many seconds is enough. */
    private const WRITE_EVERY_SECONDS = 5;

    private const TABLE = 'builder_worker_heartbeats';

    public function beat(string $name): void
    {
        $seen = $this->secondsSince($name);
        if ($seen !== null && $seen < self::WRITE_EVERY_SECONDS) {
            return;
        }

        DB::table(self::TABLE)->upsert([['name' => $name, 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]], ['name'], ['last_seen_at', 'updated_at']);
    }

    public function seenWithin(string $name, int $seconds): bool
    {
        $seen = $this->secondsSince($name);

        return $seen !== null && $seen <= $seconds;
    }

    private function secondsSince(string $name): ?int
    {
        $last = DB::table(self::TABLE)->where('name', $name)->value('last_seen_at');

        return $last === null ? null : (int) abs(now()->diffInSeconds($last));
    }
}
