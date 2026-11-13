<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Services\ProjectInspection;
use Tests\TestCase;

/**
 * A customer brings a project of their own as a Google Drive link, pays a fixed fee to have it inspected in a locked
 * box, reads the report, and finds the maintenance price in their list. We did not build it: no warranty.
 */
class ProjectInspectionTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    private const LINK = 'https://drive.google.com/file/d/1XyZ/view?usp=sharing';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.worker_token' => self::TOKEN, 'services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $usd = \App\Models\Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = \App\Models\Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        \App\Models\CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    /** Brought, and the fee paid through the gateway. */
    private function paidInspection(): BuilderProject
    {
        $uuid = $this->postJson('/ai/inspect', ['drive_url' => self::LINK, 'need' => 'عايز أضيف دفع أونلاين للمتجر'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $this->postJson('/ai/'.$uuid.'/inspect/pay', ['client_name' => 'سارة', 'client_email' => 'sara@example.com', 'client_whatsapp' => '01012345678'])->assertOk();
        [$body, $headers] = $this->signedWebhook(\Modules\AppBuilder\Models\BuilderPaymentOrder::latest('id')->value('order_id'));
        $this->postJson('/ai/webhook', $body, $headers)->assertOk()->assertJsonPath('status', 'processed');

        return $project->refresh();
    }

    public function test_a_brought_project_shows_the_inspection_fee_and_no_price(): void
    {
        $state = $this->postJson('/ai/inspect', ['drive_url' => self::LINK, 'need' => 'عايز أضيف دفع أونلاين للمتجر'])->assertOk();

        $state->assertJsonPath('inspection.status', ProjectInspection::OFFER)
            ->assertJsonPath('inspection.price', number_format(ProjectInspection::price(), 2))
            ->assertJsonPath('quote', null)
            ->assertJsonPath('requests', null);
        $this->postJson('/ai/inspect', ['drive_url' => 'https://example.com/x.zip', 'need' => 'عدل الموقع'])->assertStatus(422);
    }

    public function test_the_fee_sends_the_project_to_a_locked_inspection_and_is_its_own_invoice(): void
    {
        $project = $this->paidInspection();

        $this->assertSame([BuilderProject::QUEUED, BuilderProject::JOB_IMPORT, ProjectInspection::RUNNING], [$project->status, $project->job_kind, $project->inspection_status]);
        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        // the worker inspects it locked away; it is not the owner's import, so nothing goes to GitHub or a server
        $this->assertSame(['import', true, false, false], [$job['kind'], $job['customer_project'], $job['imported'], $job['deploy']]);
        $this->assertSame('paid', Invoice::where('user_id', $project->refresh()->user_id)->sole()->status);
    }

    public function test_the_report_reaches_the_customer_and_the_maintenance_is_priced_with_no_warranty(): void
    {
        $project = $this->paidInspection();
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/imported', [
            'profile' => '# Shop\n- Laravel 10 store', 'report' => 'فحصنا متجرك: شغال، وناقصه الدفع أونلاين.',
            'request_title' => 'دفع أونلاين للمتجر', 'request_spec' => 'Add Paymob checkout.', 'request_tokens' => 30000, 'request_complex' => false,
        ], $this->worker())->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertNull($project->warranty_until);
        $this->assertSame('فحصنا متجرك: شغال، وناقصه الدفع أونلاين.', $project->messages()->where('role', BuilderMessage::ASSISTANT)->reorder('id', 'desc')->value('content'));
        $item = $project->requests()->sole();
        $this->assertSame([BuilderRequest::UNPAID, false], [$item->status, $item->free]);
        $this->assertGreaterThan(0, (float) $item->amount);
        $this->assertSame('عايز أضيف دفع أونلاين للمتجر', $item->customer_words);
        $this->assertFalse($project->isUnderWarranty());
    }

    public function test_a_link_that_cannot_be_opened_is_sent_again_without_paying_twice(): void
    {
        $project = $this->paidInspection();
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'Drive: 403', 'cost_usd' => 0], $this->worker())->assertOk();
        $this->assertSame([BuilderProject::CHATTING, ProjectInspection::FAILED], [$project->refresh()->status, $project->inspection_status]);

        $this->postJson('/ai/'.$project->uuid.'/inspect', ['drive_url' => self::LINK, 'need' => 'نفس الطلب'])->assertOk()->assertJsonPath('inspection', null);
        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
        $this->assertSame(1, Invoice::count());
    }

    private function signedWebhook(string $merchantOrderId): array
    {
        $data = ['status' => 'SUCCESS', 'merchantOrderId' => $merchantOrderId, 'transactionId' => 'TX-5', 'signatureKeys' => ['merchantOrderId', 'status', 'transactionId']];
        $keys = $data['signatureKeys'];
        sort($keys);
        $query = http_build_query(array_combine($keys, array_map(fn ($key) => $data[$key], $keys)), '', '&', PHP_QUERY_RFC3986);

        return [['data' => $data], ['x-kashier-signature' => hash_hmac('sha256', $query, 'secret')]];
    }
}
