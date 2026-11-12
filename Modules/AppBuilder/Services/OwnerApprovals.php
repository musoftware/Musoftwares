<?php

namespace Modules\AppBuilder\Services;

use App\Services\Telegram\TelegramApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * The owner's approvals in Telegram: a card for each request or new project that waits, with what the engineer will
 * read, and buttons to approve, turn down, change the price, or approve for free. The same card is the alert when
 * something new arrives, so the owner acts from the phone without opening the site.
 */
class OwnerApprovals
{
    /** callback data: action:kind:id, for example "ap:r:12" approves request 12 */
    private const ACTION = '/^(ap|rj|pr|fr):(r|p):(\d{1,10})$/';

    private const PRICE_MINUTES = 10;

    private const PRICE_KEY = 'telegram_price_';

    public function __construct(private readonly TelegramApi $api, private readonly OwnerReview $review) {}

    /** @return array{text: string, actions: list<array{0: string, 1: string}>, links: list<array{0: string, 1: string}>} */
    public function requestCard(BuilderRequest $request): array
    {
        $project = $request->project;
        $price = $request->free ? 'مجاني' : number_format((float) $request->amount, 2).' '.$project->currency;
        $paid = $request->paid_at !== null ? 'مدفوع' : ($request->free ? 'من غير دفع' : 'مستني دفع العميل');
        $kind = ['fix' => 'تصليح', 'build' => 'تطوير', 'new_project' => 'مشروع تاني'][$request->kind] ?? $request->kind;
        $actions = [['وافق', 'ap:r:'.$request->id], ['ارفض', 'rj:r:'.$request->id]];
        if ($request->paid_at === null && ! $request->free) {
            $actions = [...$actions, ['عدّل السعر', 'pr:r:'.$request->id], ['وافق مجاناً', 'fr:r:'.$request->id]];
        }

        return [
            'text' => "طلب: {$request->title}\nالمشروع: {$project->title}\n{$kind} · {$price} · {$paid}"
                ."\n\nكلام العميل:\n".Str::limit((string) $request->customer_words, 700)
                ."\n\nملاحظة الشات للمهندس:\n".Str::limit((string) $request->spec, 900),
            'actions' => $actions,
            'links' => [['افتح الصفحة', $this->review->url($project, $request)]],
        ];
    }

    /** @return array{text: string, actions: list<array{0: string, 1: string}>, links: list<array{0: string, 1: string}>} */
    public function projectCard(BuilderProject $project): array
    {
        return [
            'text' => "مشروع جديد: {$project->title}\nالسعر: ".number_format((float) $project->amount, 2).' '.$project->currency
                ."\n\nالـ brief اللي المهندس هيبني منه:\n".Str::limit((string) $project->spec, 1500),
            'actions' => [['وافق وابدأ', 'ap:p:'.$project->id]],
            'links' => [['افتح الصفحة', route('admin.builder.show', $project).'#approve']],
        ];
    }

    /** Every card that waits for the owner, sent to this chat. */
    public function sendWaiting(string $chatId): void
    {
        $cards = [
            ...$this->waitingProjects()->map(fn (BuilderProject $project) => $this->projectCard($project)),
            ...$this->waitingRequests()->map(fn (BuilderRequest $request) => $this->requestCard($request)),
        ];
        if ($cards === []) {
            $this->api->send($chatId, 'مفيش حاجة مستنية موافقتك دلوقتي.');

            return;
        }
        foreach ($cards as $card) {
            $this->api->send($chatId, $card['text'], $card['links'], actions: $card['actions']);
        }
    }

    /** A button on a card: what happened, in one line for the owner. */
    public function act(string $chatId, string $data): string
    {
        if (! preg_match(self::ACTION, $data, $match)) {
            return 'الزرار ده قديم.';
        }
        [, $action, $kind, $id] = $match;

        return $kind === 'p' ? $this->actOnProject((int) $id) : $this->actOnRequest($chatId, $action, (int) $id);
    }

    /** The owner typed the new price asked by "change the price": saved, and the card comes back to approve. */
    public function priceTyped(string $chatId, string $text): bool
    {
        $id = Cache::store('file')->get(self::PRICE_KEY.$chatId);
        if ($id === null) {
            return false;
        }
        $request = $this->open((int) $id);
        if ($request === null) {
            Cache::store('file')->forget(self::PRICE_KEY.$chatId);
            $this->api->send($chatId, 'الطلب ده مبقاش مستني.');

            return true;
        }
        $amount = (float) str_replace(',', '.', (string) preg_replace('/[^\d.,]/', '', $this->latinDigits($text)));
        if ($amount <= 0) {
            // still waiting for the price
            $this->api->send($chatId, 'اكتب رقم بس، مثلاً 17');

            return true;
        }

        Cache::store('file')->forget(self::PRICE_KEY.$chatId);
        $this->review->save($request, ['amount' => $amount]);
        $card = $this->requestCard($request->refresh());
        $this->api->send($chatId, $card['text'], $card['links'], actions: $card['actions']);

        return true;
    }

    private function actOnRequest(string $chatId, string $action, int $id): string
    {
        $request = $this->open($id);
        if ($request === null) {
            return 'الطلب ده اتقفل أو بدأ خلاص.';
        }
        $project = $request->project;

        return match ($action) {
            'ap' => $this->approved($project, $request),
            'rj' => $this->rejected($project, $request),
            'fr' => $this->approvedFree($project, $request),
            'pr' => $this->askPrice($chatId, $request),
        };
    }

    private function approved(BuilderProject $project, BuilderRequest $request): string
    {
        $this->review->approve($project, $request, []);

        return 'اتوافق على "'.$request->title.'"'.($request->refresh()->status === BuilderRequest::UNPAID ? '، وهيبدأ أول ما العميل يدفع.' : '، والشغل بيبدأ.');
    }

    private function rejected(BuilderProject $project, BuilderRequest $request): string
    {
        $this->review->reject($project, $request, '');

        return 'اترفض "'.$request->title.'"، والعميل اتبلّغ في الشات.';
    }

    private function approvedFree(BuilderProject $project, BuilderRequest $request): string
    {
        if ($request->paid_at !== null) {
            return 'الطلب ده اتدفع خلاص، مينفعش يبقى مجاني.';
        }
        $this->review->approveFree($project, $request);

        return 'اتوافق على "'.$request->title.'" مجاناً، والشغل بيبدأ.';
    }

    private function askPrice(string $chatId, BuilderRequest $request): string
    {
        if ($request->paid_at !== null || $request->free) {
            return 'السعر مينفعش يتغير بعد الدفع.';
        }
        Cache::store('file')->put(self::PRICE_KEY.$chatId, $request->id, now()->addMinutes(self::PRICE_MINUTES));

        return 'اكتب السعر الجديد بالدولار (رقم بس) لـ "'.$request->title.'".';
    }

    private function actOnProject(int $id): string
    {
        $project = BuilderProject::find($id);
        if ($project === null || ! $project->waitsForOwner()) {
            return 'المشروع ده مبقاش مستني موافقة.';
        }
        $this->review->approveProject($project, null);

        return 'اتوافق على "'.$project->title.'"، والتنفيذ بيبدأ.';
    }

    /** A request the owner may still act on: not started, not approved yet. */
    private function open(int $id): ?BuilderRequest
    {
        return BuilderRequest::whereKey($id)->whereIn('status', [BuilderRequest::UNPAID, BuilderRequest::READY])->whereNull('approved_at')->first();
    }

    /** @return Collection<int, BuilderRequest> */
    private function waitingRequests(): Collection
    {
        return BuilderRequest::with('project')->whereIn('status', [BuilderRequest::UNPAID, BuilderRequest::READY])->whereNull('approved_at')->latest('id')->limit(15)->get();
    }

    /** @return Collection<int, BuilderProject> */
    private function waitingProjects(): Collection
    {
        return BuilderProject::whereIn('status', [BuilderProject::QUEUED, BuilderProject::DESIGNING])->whereNull('owner_approved_at')->where('version', 0)
            ->latest('id')->limit(10)->get()->filter->waitsForOwner()->values();
    }

    /** "١٧" typed on an Arabic keyboard is 17. */
    private function latinDigits(string $text): string
    {
        return strtr($text, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.']);
    }
}
