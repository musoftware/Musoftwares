<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\CostTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderAiSpending;
use Modules\AppBuilder\Services\AiCosts;
use Tests\TestCase;

class AiCostsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Feature\Concerns\SeedsUsdEgpRates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        config(['builder.ai_cost.free_credit_usd' => 200]);
    }

    public function test_only_what_is_spent_past_the_free_credit_is_a_cost(): void
    {
        $this->assertSame(0.0, AiCosts::pastFreeCredit(0, 150));
        $this->assertSame(30.0, AiCosts::pastFreeCredit(150, 80));
        $this->assertSame(40.0, AiCosts::pastFreeCredit(230, 40));
    }

    public function test_each_day_becomes_one_cost_once_the_free_credit_is_used_up(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Africa/Cairo'));
        $this->spend(150, 'build');
        $this->spend(20, 'chat');

        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Africa/Cairo'));
        $this->spend(45.5, 'build');

        $this->travelTo(CarbonImmutable::parse('2026-11-12 09:00', 'Africa/Cairo'));
        $this->spend(9, 'chat');

        $this->assertSame(1, app(AiCosts::class)->bookDue());

        // day one stayed inside the free credit (170 of 200); day two went 15.50 past it; today waits for tomorrow
        $cost = CostTransaction::sole();
        $this->assertSame(15.5, (float) $cost->amount);
        $this->assertSame('Claude AI', $cost->reason);
        $this->assertSame('2026-11-11', $cost->created_at->timezone('Africa/Cairo')->toDateString());
        $this->assertSame(1, BuilderAiSpending::whereNull('booked_at')->count());

        // booking again adds nothing twice
        $this->assertSame(0, app(AiCosts::class)->bookDue());
        $this->travelTo(CarbonImmutable::parse('2026-11-13 01:00', 'Africa/Cairo'));
        $this->assertSame(1, app(AiCosts::class)->bookDue());
        $this->assertSame(9.0, (float) CostTransaction::latest('id')->value('amount'));
    }

    private function spend(float $usd, string $source): void
    {
        app(AiCosts::class)->record(null, $source, $usd);
    }
}
