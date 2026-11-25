<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;
use Modules\AppBuilder\Models\BuilderSiteTour;

/**
 * A customer who wants a copy of a site gets a real look at it first, like a tester would: the worker opens the
 * site in a browser on the owner's PC, walks through its pages (logged in with the customer's test account when
 * they gave one), takes a picture of each, and writes down the screens, menus, forms and tables it found.
 * The chat prices the copy from that, and the build gets the same pictures and notes.
 * The worker only reads: it follows links and fills in the login form, it never sends any other form.
 */
class SiteTours
{
    /** The test account the customer gives for the site, saved like any other access detail. */
    public const LOGIN_URL = 'clone_login_url';

    public const USERNAME = 'clone_username';

    public const PASSWORD = 'clone_password';

    private const HEARTBEAT = 'tours';

    /** Nobody took it in this long: the worker is off, and the chat goes on without the tour. */
    private const UNTAKEN_MINUTES = 3;

    /** A tour taken this long ago with no answer is over; the chat goes on with what it has. */
    private const TAKEN_MINUTES = 20;

    private const REPORT_CHARS = 60000;

    public function __construct(
        private readonly AttachmentStore $attachments,
        private readonly ProjectSecrets $secrets,
        private readonly WorkerHeartbeat $heartbeat,
    ) {}

    /** Null when the address is not a web address, a tour is already running, or today's tours are used up. */
    public function ask(BuilderProject $project, string $url): ?BuilderSiteTour
    {
        $url = trim($url);
        if (! $this->isWebAddress($url) || $this->openTour($project) !== null || $this->toursToday($project) >= (int) config('builder.tours.per_day')) {
            return null;
        }

        return $project->siteTours()->create(['url' => mb_substr($url, 0, 2000), 'status' => BuilderSiteTour::PENDING]);
    }

    public function isTouring(BuilderProject $project): bool
    {
        return $this->openTour($project) !== null;
    }

    /** @return list<array<string, mixed>> the tours for the worker now, marked as taken, with the test login */
    public function forWorker(): array
    {
        $this->heartbeat->beat(self::HEARTBEAT);
        $tours = BuilderSiteTour::where('status', BuilderSiteTour::PENDING)
            ->where('created_at', '>=', now()->subMinutes(self::UNTAKEN_MINUTES))
            ->with('project')->oldest('id')->limit(2)->get();

        return $tours->map(function (BuilderSiteTour $tour): array {
            $tour->update(['status' => BuilderSiteTour::TAKEN, 'taken_at' => now()]);

            return [
                'id' => $tour->id,
                'project' => $tour->project->uuid,
                'url' => $tour->url,
                'max_pages' => (int) config('builder.tours.max_pages'),
                'login' => $this->login($tour->project),
            ];
        })->values()->all();
    }

    /**
     * What the worker found: a short line and the pictures for the customer, the full notes for the chat and the
     * build. False when this tour was already finished (sent twice, or over its time).
     *
     * @param  list<UploadedFile>  $pictures
     */
    public function complete(BuilderSiteTour $tour, array $result, array $pictures): bool
    {
        $finished = BuilderSiteTour::whereKey($tour->id)->whereIn('status', [BuilderSiteTour::PENDING, BuilderSiteTour::TAKEN])
            ->update(['status' => BuilderSiteTour::DONE, 'finished_at' => now()]);
        if ($finished === 0) {
            return false;
        }

        $ok = (bool) ($result['ok'] ?? false) && $pictures !== [];
        $tour->update([
            'status' => $ok ? BuilderSiteTour::DONE : BuilderSiteTour::FAILED,
            'logged_in' => (bool) ($result['logged_in'] ?? false),
            'pages_seen' => count($pictures),
            'reason' => $ok ? null : mb_substr((string) ($result['reason'] ?? 'The site could not be opened.'), 0, 300),
        ]);
        if ($ok) {
            $this->tell($tour->refresh(), (string) ($result['report'] ?? ''), $pictures);
        }

        return true;
    }

    /** The tour that ran out of time, now marked failed, so the chat goes on without it; null when none did. */
    public function finishLate(BuilderProject $project): ?BuilderSiteTour
    {
        $tour = $this->openTour($project, withLate: true);
        if ($tour === null || ! $this->isLate($tour)) {
            return null;
        }

        $updated = BuilderSiteTour::whereKey($tour->id)->where('status', $tour->status)->update([
            'status' => BuilderSiteTour::FAILED,
            'finished_at' => now(),
            'reason' => $tour->status === BuilderSiteTour::PENDING ? 'the engineering computer did not take the tour' : 'the tour took too long',
        ]);

        return $updated > 0 ? $tour->refresh() : null;
    }

    /** A line under the picture the customer sees, and the notes the chat model and the build read. */
    private function tell(BuilderSiteTour $tour, string $report, array $pictures): void
    {
        $project = $tour->project;
        $line = __($tour->logged_in ? 'builder.tour_done_logged_in' : 'builder.tour_done', ['pages' => count($pictures)], $project->locale);
        $message = $project->messages()->create([
            'role' => BuilderMessage::TOUR,
            'content' => $line,
            'payload' => ['tour' => [
                'id' => $tour->id,
                'url' => $tour->url,
                'logged_in' => $tour->logged_in,
                'report' => $this->secrets->mask($project, mb_substr(trim($report), 0, self::REPORT_CHARS)),
            ]],
        ]);
        $this->attachments->attach($message, $pictures);
    }

    /** @return array{url: string, username: string, password: string}|null */
    private function login(BuilderProject $project): ?array
    {
        $saved = collect($this->secrets->forWorker($project))->pluck('value', 'name');
        if (! $saved->has(self::USERNAME) || ! $saved->has(self::PASSWORD)) {
            return null;
        }

        return ['url' => (string) $saved->get(self::LOGIN_URL, ''), 'username' => (string) $saved[self::USERNAME], 'password' => (string) $saved[self::PASSWORD]];
    }

    private function openTour(BuilderProject $project, bool $withLate = false): ?BuilderSiteTour
    {
        $tour = $project->siteTours()->whereIn('status', [BuilderSiteTour::PENDING, BuilderSiteTour::TAKEN])->latest('id')->first();

        return $tour !== null && ($withLate || ! $this->isLate($tour)) ? $tour : null;
    }

    private function isLate(BuilderSiteTour $tour): bool
    {
        return $tour->status === BuilderSiteTour::PENDING
            ? $tour->created_at->lt(now()->subMinutes(self::UNTAKEN_MINUTES))
            : ($tour->taken_at ?? $tour->created_at)->lt(now()->subMinutes(self::TAKEN_MINUTES));
    }

    private function toursToday(BuilderProject $project): int
    {
        return $project->siteTours()->where('created_at', '>=', now()->subDay())->count();
    }

    private function isWebAddress(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
