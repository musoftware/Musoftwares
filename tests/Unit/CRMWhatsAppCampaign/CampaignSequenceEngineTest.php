<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignSequenceEngine;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppTemplateRenderer;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CampaignSequenceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignSequenceEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->engine = new CampaignSequenceEngine(new WhatsAppTemplateRenderer());
    }

    public function test_step_delay_calculation_minutes(): void
    {
        $step = new WhatsAppCampaignSequenceStep(['delay_minutes' => 30, 'delay_unit' => 'minutes']);
        $this->assertEquals(30, $step->getDelayInMinutes());
    }

    public function test_step_delay_calculation_hours(): void
    {
        $step = new WhatsAppCampaignSequenceStep(['delay_minutes' => 2, 'delay_unit' => 'hours']);
        $this->assertEquals(120, $step->getDelayInMinutes());
    }

    public function test_step_delay_calculation_days(): void
    {
        $step = new WhatsAppCampaignSequenceStep(['delay_minutes' => 1, 'delay_unit' => 'days']);
        $this->assertEquals(1440, $step->getDelayInMinutes());
    }

    public function test_step_type_helpers(): void
    {
        $send = new WhatsAppCampaignSequenceStep(['action_type' => 'send_message']);
        $wait = new WhatsAppCampaignSequenceStep(['action_type' => 'wait']);
        $cond = new WhatsAppCampaignSequenceStep(['action_type' => 'condition']);
        $exit = new WhatsAppCampaignSequenceStep(['action_type' => 'exit']);

        $this->assertTrue($send->isSendMessage());
        $this->assertTrue($wait->isWait());
        $this->assertTrue($cond->isCondition());
        $this->assertTrue($exit->isExit());
    }

    public function test_exit_conditions_check_replied(): void
    {
        $delivery = new WhatsAppCampaignDelivery(['has_replied' => true]);
        $sequence = new WhatsAppCampaignSequence(['exit_conditions' => [['field' => 'replied']]]);
        $step = new WhatsAppCampaignSequenceStep(['stop_on_reply' => false]);
        $step->setRelation('sequence', $sequence);

        $reflection = new \ReflectionMethod($this->engine, 'shouldExit');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->engine, $delivery, $step);

        $this->assertTrue($result);
    }
}
