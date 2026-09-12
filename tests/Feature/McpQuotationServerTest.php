<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class McpQuotationServerTest extends TestCase
{
    use RefreshDatabase;
    public function test_mcp_tools_list_endpoint_returns_tools(): void
    {
        $response = $this->getJson('/api/mcp/tools');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'server' => ['name', 'version', 'protocolVersion'],
            'tools',
        ]);
        $response->assertJsonFragment(['name' => 'generate_premium_quotation_pdf']);
        $response->assertJsonFragment(['name' => 'get_estimator_rate_card']);
        $response->assertJsonFragment(['name' => 'get_quotation']);
    }

    public function test_mcp_initialize_handshake(): void
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => ['name' => 'claude-ai', 'version' => '1.0'],
            ],
        ];

        $response = $this->postJson('/api/mcp/rpc', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'serverInfo' => [
                    'name' => 'musoftware-quotations',
                    'version' => '1.0.0',
                ],
            ],
        ]);
    }

    public function test_mcp_tools_call_generates_quotation_and_links(): void
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'generate_premium_quotation_pdf',
                'arguments' => [
                    'project_name' => 'Pepper Sauce Store',
                    'client_name' => 'Ahmed Rehab',
                    'client_business' => 'HotSauce Co.',
                    'currency' => 'USD',
                    'platforms' => [
                        [
                            'title' => 'E-commerce Storefront',
                            'count' => 6,
                            'unit' => 'Page',
                            'unit_price_usd' => 10.0,
                        ],
                    ],
                    'addons' => [
                        [
                            'title' => 'Admin Panel & Payment Gateway',
                            'price_usd' => 90.0,
                        ],
                    ],
                    'discount_usd' => 10.0,
                ],
            ],
        ];

        $response = $this->postJson('/api/mcp/rpc', $payload);

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertArrayHasKey('result', $responseData);
        $this->assertArrayHasKey('meta', $responseData['result']);
        
        $code = $responseData['result']['meta']['code'];
        $this->assertNotEmpty($code);
        $this->assertEquals(140.0, $responseData['result']['meta']['total_usd']); // (6*10) + 90 - 10 = 140

        // Verify quote was stored in cache
        $cached = Cache::get("quotation:{$code}");
        $this->assertNotNull($cached);
        $this->assertEquals('Ahmed Rehab', $cached['client_name']);
        $this->assertEquals('Pepper Sauce Store', $cached['platforms_summary']);

        // Verify public quotation web view
        $viewResponse = $this->get("/quotation/{$code}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Pepper Sauce Store');
        $viewResponse->assertSee('HotSauce Co.');
        $viewResponse->assertSee('Mahmoud Amin M.');

        // Verify public quotation PDF endpoint
        $pdfResponse = $this->get("/quotation/{$code}/pdf");
        $pdfResponse->assertStatus(200);
    }

    public function test_mcp_get_rate_card_tool(): void
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get_estimator_rate_card',
                'arguments' => [
                    'exchange_rate' => 50.0,
                ],
            ],
        ];

        $response = $this->postJson('/api/mcp/rpc', $payload);

        $response->assertStatus(200);
        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Website / Web App', $text);
    }

    public function test_mcp_openapi_spec_endpoint(): void
    {
        $response = $this->getJson('/api/mcp/openapi.json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version'],
            'paths',
        ]);
    }

    public function test_mcp_sse_session_messaging(): void
    {
        $sessionId = 'test-session-1234';

        $payload = [
            'jsonrpc' => '2.0',
            'id' => 10,
            'method' => 'ping',
        ];

        $response = $this->postJson("/api/mcp/messages?sessionId={$sessionId}", $payload);

        $response->assertStatus(202);
        $response->assertJson(['status' => 'accepted']);

        $outbox = Cache::get("mcp_outbox:{$sessionId}");
        $this->assertNotEmpty($outbox);
        $this->assertEquals(10, $outbox[0]['id']);
    }

    public function test_demo_quotation_preview_endpoint(): void
    {
        $response = $this->get('/quotation/demo');

        $response->assertStatus(200);
        $response->assertSee('Pepper Sauce Store');
        $response->assertSee('MUSOFTWARE ARCHITECTURE');
        $response->assertSee('OFFICIAL');
    }
}


