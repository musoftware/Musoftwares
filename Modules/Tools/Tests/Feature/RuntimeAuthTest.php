<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows connection page with code and port', function () {
    $response = $this->get('/runtime/connect?code=ABC123XYZ&port=18400');

    $response->assertStatus(200)
             ->assertInertia(fn (AssertableInertia $page) => $page
                 ->component('Tools/RuntimeConnect')
                 ->has('code')
                 ->where('code', 'ABC123XYZ')
                 ->where('port', 18400)
                 ->where('userName', $this->user->name)
             );
});

it('handles missing code on connection page', function () {
    $response = $this->get('/runtime/connect');

    $response->assertStatus(200)
             ->assertInertia(fn (AssertableInertia $page) => $page
                 ->component('Tools/RuntimeConnect')
                 ->where('missingCode', true)
                 ->where('code', '')
             );
});

it('authorizes and sends token to runtime', function () {
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
});

it('revokes token if local agent is unreachable', function () {
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
});

it('requires valid code length', function () {
    $response = $this->post('/runtime/connect', [
        'code' => 'short', // less than 10
        'port' => 18400,
    ]);

    $response->assertSessionHasErrors('code');
});
