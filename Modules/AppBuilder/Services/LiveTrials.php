<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Collection;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Live trials of a delivered program: the customer asks, the worker (which always calls the site) picks the
 * request up, starts a desktop with the program in Chromium, and reports the link and password back.
 * The worker also ends each trial when its time is up.
 */
class LiveTrials
{
    public const REQUESTED = 'requested';

    public const LIVE = 'live';

    public const FAILED = 'failed';

    /** A request older than this was not picked up (the worker is off); the customer may ask again. */
    private const REQUEST_TIMEOUT_MINUTES = 15;

    public function __construct(private readonly DeliveryStore $store) {}

    public function canRequest(BuilderProject $project): bool
    {
        // an imported project runs on its own server, with its own data; a trial here would run it without either
        return $project->hasDelivery() && ! $project->isImported() && ! $this->isLive($project) && ! $this->isWaiting($project);
    }

    public function request(BuilderProject $project): bool
    {
        if (! $this->canRequest($project)) {
            return false;
        }

        $project->update(['demo_status' => self::REQUESTED, 'demo_requested_at' => now(), 'demo_url' => null, 'demo_password' => null, 'demo_expires_at' => null]);

        return true;
    }

    /** @return Collection<int, array{uuid: string, kind: string}> trials the worker should start now */
    public function pending(): Collection
    {
        return BuilderProject::where('demo_status', self::REQUESTED)
            ->where('demo_requested_at', '>=', now()->subMinutes(self::REQUEST_TIMEOUT_MINUTES))
            ->orderBy('demo_requested_at')
            ->get()
            ->map(fn (BuilderProject $project) => ['uuid' => $project->uuid, 'kind' => $this->kind($project)])
            ->values();
    }

    public function started(BuilderProject $project, string $url, string $password, int $minutes): void
    {
        $project->update([
            'demo_status' => self::LIVE,
            'demo_url' => $url,
            'demo_password' => $password,
            'demo_expires_at' => now()->addMinutes($minutes),
        ]);
        app(BuilderPush::class)->notify($project, 'trial');
    }

    public function failed(BuilderProject $project): void
    {
        $project->update(['demo_status' => self::FAILED, 'demo_url' => null, 'demo_password' => null]);
    }

    public function isLive(BuilderProject $project): bool
    {
        return $project->demo_status === self::LIVE && $project->demo_expires_at?->isFuture();
    }

    public function isWaiting(BuilderProject $project): bool
    {
        return $project->demo_status === self::REQUESTED
            && $project->demo_requested_at?->greaterThanOrEqualTo(now()->subMinutes(self::REQUEST_TIMEOUT_MINUTES));
    }

    /** A Chrome extension is loaded into the browser; anything else is opened as a page. */
    public function kind(BuilderProject $project): string
    {
        return $this->store->file($project, 'extension/manifest.json') ? 'extension' : 'page';
    }
}
