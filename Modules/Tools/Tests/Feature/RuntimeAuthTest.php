<?php

namespace Modules\Tools\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RuntimeAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_shows_connection_page_with_code_and_port()
    {
        $response = $this->get('/runtime/connect?code=ABC123XYZ&port=18400');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/RuntimeConnect')
                     ->has('code')
                     ->where('code', 'ABC123XYZ')
                     ->where('port', 18400)
                     ->where('userName', $this->user->name)
                 );
    }

    public function test_handles_missing_code_on_connection_page()
    {
        $response = $this->get('/runtime/connect');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/RuntimeConnect')
                     ->where('missingCode', true)
                     ->where('code', '')
                 );
    }

    public function test_authorizes_and_sends_token_to_runtime()
    {
        Http::fake([
            'http://127.0.0.1:18400/auth/callback' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->post('/runtime/connect', [
            'code' => 'ABC123XYZ456',
            'port' => 18400,
        ]);

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/RuntimeConnect')
                     ->where('success', true)
                 );

        // Assert a token was created for the user
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => 'musoftware-runtime',
        ]);

        // Assert HTTP request was made to the local agent
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() == 'http://127.0.0.1:18400/auth/callback' &&
                   $request['device_code'] == 'ABC123XYZ456' &&
                   isset($request['token']) &&
                   $request['userId'] == (string) $this->user->id;
        });
    }

    public function test_revokes_token_if_local_agent_is_unreachable()
    {
        Http::fake([
            'http://127.0.0.1:18400/auth/callback' => Http::response(null, 500),
        ]);

        $response = $this->post('/runtime/connect', [
            'code' => 'ABC123XYZ456',
            'port' => 18400,
        ]);

        $response->assertSessionHasErrors('callback');

        // Token should have been deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_requires_valid_code_length()
    {
        $response = $this->post('/runtime/connect', [
            'code' => 'short', // less than 10
            'port' => 18400,
        ]);

        $response->assertSessionHasErrors('code');
    }
}
