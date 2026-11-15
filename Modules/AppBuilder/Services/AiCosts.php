<?php

namespace Modules\AppBuilder\Services;

use App\Models\CostTransaction;
use App\Models\Currency;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderAiSpending;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * What Claude costs us. Each build, failed try, release and chat answer is kept as it happens; once a day the
 * day's total becomes one business cost on the costs page. The credit Claude gave for free is used up first,
 * so only what is spent past it is a cost.
 */
class AiCosts
{
    private const TIMEZONE = 'Africa/Cairo';

    public function record(?BuilderProject $project, string $source, float $usd): void
    {
        if ($usd <= 0) {
            return;
        }

        BuilderAiSpending::create(['project_id' => $project?->id, 'source' => $source, 'usd' => round($usd, 6)]);
    }

    public function recordChat(BuilderProject $project, int $inputTokens, int $outputTokens): void
    {
        $prices = config('builder.chat_prices');
        $this->record($project, BuilderAiSpending::CHAT, ($inputTokens * $prices['input_usd_per_mtok'] + $outputTokens * $prices['output_usd_per_mtok']) / 1_000_000);
    }

    /** Books every finished day that is not booked yet, oldest first. @return int how many costs were added */
    public function bookDue(): int
    {
        $todayStarts = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $days = BuilderAiSpending::whereNull('booked_at')->where('created_at', '<', $todayStarts->utc())->orderBy('id')->get()
            ->groupBy(fn (BuilderAiSpending $row) => $row->created_at->timezone(self::TIMEZONE)->toDateString())
            ->sortKeys();

        return $days->reduce(fn (int $added, Collection $rows, string $day) => $added + ($this->bookDay($day, $rows) ? 1 : 0), 0);
    }

    private function bookDay(string $day, Collection $rows): bool
    {
        return DB::transaction(function () use ($day, $rows): bool {
            $before = (float) BuilderAiSpending::whereNotNull('booked_at')->sum('usd');
            $cost = $this->pastFreeCredit($before, (float) $rows->sum('usd'));
            $costId = $cost > 0 ? $this->addCost($day, $cost) : null;

            BuilderAiSpending::whereKey($rows->modelKeys())->update(['booked_at' => now(), 'cost_transaction_id' => $costId]);
            Log::info('Claude cost booked', ['day' => $day, 'spent_usd' => round((float) $rows->sum('usd'), 4), 'cost_usd' => $cost]);

            return $costId !== null;
        });
    }

    /** The part of this spend that falls past the free credit. */
    public static function pastFreeCredit(float $spentBefore, float $spend): float
    {
        $free = (float) config('builder.ai_cost.free_credit_usd');

        return round(max(0, $spentBefore + $spend - $free) - max(0, $spentBefore - $free), 2);
    }

    private function addCost(string $day, float $usd): ?int
    {
        $endOfDay = CarbonImmutable::parse($day, self::TIMEZONE)->endOfDay()->utc();

        return CostTransaction::add_cost_balance(null, $usd, (string) config('builder.ai_cost.reason'), Currency::resolve('USD')?->id, null, $endOfDay);
    }
}
