<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderProject;
use Tests\TestCase;
use ZipArchive;

/** The customer asks for a live trial, the worker starts it and reports the link, and it closes on time. */
class LiveTrialTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.worker_token' => self::TOKEN]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bld').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'program.zip', 'application/zip', null, true);
    }

    private function delivered(array $files): BuilderProject
    {
        $project = BuilderProject::create(['ip' => '127.0.0.1', 'locale' => 'ar', 'status' => BuilderProject::BUILDING, 'title' => 'x', 'spec' => 'x']);
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip($files), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        return $project->refresh();
    }

    public function test_the_customer_asks_for_a_trial_and_gets_the_link_once_the_worker_starts_it(): void
    {
        $project = $this->delivered(['index.html' => '<h1>Menu</h1>']);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('trial.can_request', true)->assertJsonPath('trial.url', null);

        $this->postJson('/ai/'.$project->uuid.'/trial')->assertOk()->assertJsonPath('trial.waiting', true);
        $this->postJson('/ai/'.$project->uuid.'/trial')->assertStatus(409);

        $this->getJson('/api/builder/worker/trials', $this->worker())->assertOk()
            ->assertJsonPath('trials.0.uuid', $project->uuid)->assertJsonPath('trials.0.kind', 'page');
        $this->postJson('/api/builder/worker/trials/'.$project->uuid.'/started', ['url' => 'https://demo-ab12.musoftwares.com/vnc.html', 'password' => 'k3y9x2pq', 'minutes' => 45], $this->worker())->assertOk();

        $this->getJson('/ai/'.$project->uuid.'/state')
            ->assertJsonPath('trial.url', 'https://demo-ab12.musoftwares.com/vnc.html')
            ->assertJsonPath('trial.password', 'k3y9x2pq')
            ->assertJsonPath('trial.can_request', false);
        $this->getJson('/api/builder/worker/trials', $this->worker())->assertJsonCount(0, 'trials');
    }

    public function test_an_extension_gets_its_trial_by_itself_and_an_ended_trial_hides_the_link(): void
    {
        $project = $this->delivered(['SETUP.md' => '# Setup', 'extension/manifest.json' => '{"manifest_version":3}']);

        $this->getJson('/api/builder/worker/trials', $this->worker())->assertJsonPath('trials.0.kind', 'extension');
        $this->postJson('/api/builder/worker/trials/'.$project->uuid.'/started', ['url' => 'https://x.trycloudflare.com/vnc.html', 'password' => 'p4ss5word', 'minutes' => 45], $this->worker())->assertOk();

        $this->travel(46)->minutes();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('trial.url', null)->assertJsonPath('trial.password', null)->assertJsonPath('trial.can_request', true);
    }

    public function test_no_trial_before_delivery_and_the_worker_api_needs_the_token(): void
    {
        $project = BuilderProject::create(['ip' => '127.0.0.1', 'locale' => 'ar']);

        $this->postJson('/ai/'.$project->uuid.'/trial')->assertStatus(409);
        $this->getJson('/api/builder/worker/trials')->assertUnauthorized();
        $this->postJson('/api/builder/worker/trials/'.$project->uuid.'/started', ['url' => 'https://x.example.com', 'password' => 'x', 'minutes' => 10])->assertUnauthorized();
    }
}
