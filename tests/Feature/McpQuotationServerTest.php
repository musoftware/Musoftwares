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

    public function test_mcp_direct_rest_generate_endpoint(): void
    {
        $payload = [
            'project_name' => 'E-Commerce Marketplace',
            'client_name' => 'Global Retailers LLC',
            'currency' => 'USD',
            'platforms' => [
                ['title' => 'Web App', 'count' => 5, 'unit' => 'Page', 'unit_price_usd' => 10.0],
            ],
            'addons' => [
                ['title' => 'Stripe Payments', 'price_usd' => 50.0],
            ],
        ];

        $response = $this->postJson('/api/mcp/generate', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'project_name' => 'E-Commerce Marketplace',
            'total_usd' => 100,
        ]);
        $this->assertNotEmpty($response->json('pdf_url'));
        $this->assertNotEmpty($response->json('view_url'));
    }

    public function test_mcp_direct_tool_endpoint(): void
    {
        $payload = [
            'project_name' => 'Custom ERP Architecture',
            'client_name' => 'Logistics Partner',
            'currency' => 'USD',
            'platforms' => [
                ['title' => 'ERP Dashboard', 'count' => 10, 'unit' => 'Screen', 'unit_price_usd' => 20.0],
            ],
        ];

        $response = $this->postJson('/api/mcp/tools/generate_premium_quotation_pdf', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total_usd' => 200,
        ]);
        $this->assertNotEmpty($response->json('pdf_url'));
    }

    public function test_mcp_direct_rate_card_endpoint(): void
    {
        $response = $this->getJson('/api/mcp/rate-card');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'exchange_rate',
            'rate_card' => ['platforms', 'modules'],
        ]);
    }

    public function test_mcp_sse_non_streaming_graceful_response(): void
    {
        // When a non-streaming HTTP client (like ChatGPT) hits /api/mcp/sse
        $response = $this->getJson('/api/mcp/sse');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'online',
        ]);
        $this->assertNotEmpty($response->json('direct_endpoints.generate_quotation'));
    }

    public function test_mcp_sse_post_graceful_handling(): void
    {
        // When a client mistakenly POSTs to /api/mcp/sse
        $payload = [
            'project_name' => 'AI Generated Project',
            'client_name' => 'Enterprise Client',
            'platforms' => [
                ['title' => 'Portal', 'count' => 4, 'unit_price_usd' => 10.0],
            ],
        ];

        $response = $this->postJson('/api/mcp/sse', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('pdf_url'));
    }
}


