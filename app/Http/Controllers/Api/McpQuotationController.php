<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProjectEstimatorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpQuotationController extends Controller
{
    private const PROTOCOL_VERSION = '2024-11-05';
    private const SERVER_NAME = 'musoftware-quotations';
    private const SERVER_VERSION = '1.0.0';

    /**
     * SSE Stream endpoint for Model Context Protocol (MCP) clients.
     */
    public function sse(Request $request): StreamedResponse
    {
        $sessionId = (string) Str::uuid();

        return new StreamedResponse(function () use ($sessionId) {
            // Disable output buffering
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // 1. Emit MCP endpoint event as per spec
            $endpointUrl = url("/api/mcp/messages?sessionId={$sessionId}");
            echo "event: endpoint\n";
            echo "data: {$endpointUrl}\n\n";
            flush();

            // 2. Poll for queued outgoing messages or send keepalive
            $startTime = time();
            $maxDuration = 45; // 45 seconds per connection cycle

            while (time() - $startTime < $maxDuration) {
                if (connection_aborted()) {
                    break;
                }

                $cacheKey = "mcp_outbox:{$sessionId}";
                $messages = Cache::pull($cacheKey);

                if (!empty($messages) && is_array($messages)) {
                    foreach ($messages as $msg) {
                        echo "event: message\n";
                        echo "data: " . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n\n";
                        flush();
                    }
                } else {
                    // Send lightweight keep-alive ping every 15 seconds
                    echo ": ping\n\n";
                    flush();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Messages endpoint for MCP SSE clients.
     */
    public function messages(Request $request): JsonResponse
    {
        $sessionId = $request->query('sessionId');
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        $response = $this->handleJsonRpc($payload, $sessionId);

        // If session exists, also queue the message in the SSE outbox
        if (!empty($sessionId)) {
            $cacheKey = "mcp_outbox:{$sessionId}";
            $existing = Cache::get($cacheKey, []);
            $existing[] = $response;
            Cache::put($cacheKey, $existing, now()->addMinutes(5));

            return response()->json(['status' => 'accepted'], 202);
        }

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Stateless HTTP JSON-RPC endpoint for modern MCP or direct HTTP callers.
     */
    public function rpc(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        $response = $this->handleJsonRpc($payload);

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Public tools discovery endpoint.
     */
    public function toolsList(): JsonResponse
    {
        return response()->json([
            'server' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
                'protocolVersion' => self::PROTOCOL_VERSION,
            ],
            'tools' => $this->getToolsDefinition(),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * OpenAPI 3.1 specification for Custom GPTs and AI actions.
     */
    public function openapi(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Musoftware Official Quotation Engine API',
                'description' => 'Automated, pixel-perfect executive project quotations and PDF generation from Musoftware.',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => url('/api/mcp')],
            ],
            'paths' => [
                '/rpc' => [
                    'post' => [
                        'summary' => 'Execute MCP Tool or JSON-RPC Method',
                        'operationId' => 'executeMcp',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['jsonrpc', 'method', 'id'],
                                        'properties' => [
                                            'jsonrpc' => ['type' => 'string', 'example' => '2.0'],
                                            'method' => ['type' => 'string', 'example' => 'tools/call'],
                                            'id' => ['type' => 'integer', 'example' => 1],
                                            'params' => ['type' => 'object'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'JSON-RPC response'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Dispatch JSON-RPC methods.
     */
    private function handleJsonRpc(array $payload, ?string $sessionId = null): array
    {
        $id = $payload['id'] ?? null;
        $method = $payload['method'] ?? '';
        $params = $payload['params'] ?? [];

        if ($method === 'initialize') {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => self::PROTOCOL_VERSION,
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => false,
                        ],
                    ],
                    'serverInfo' => [
                        'name' => self::SERVER_NAME,
                        'version' => self::SERVER_VERSION,
                    ],
                ],
            ];
        }

        if ($method === 'notifications/initialized') {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => ['status' => 'ready'],
            ];
        }

        if ($method === 'ping') {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => new \stdClass(),
            ];
        }

        if ($method === 'tools/list') {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'tools' => $this->getToolsDefinition(),
                ],
            ];
        }

        if ($method === 'tools/call') {
            $toolName = $params['name'] ?? '';
            $arguments = $params['arguments'] ?? [];

            $result = $this->handleToolCall($toolName, $arguments);

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32601,
                'message' => "Method '{$method}' not found",
            ],
        ];
    }

    /**
     * Execute designated MCP tool.
     */
    private function handleToolCall(string $toolName, array $args): array
    {
        if ($toolName === 'generate_premium_quotation_pdf') {
            return $this->toolGenerateQuotation($args);
        }

        if ($toolName === 'get_estimator_rate_card') {
            return $this->toolGetRateCard($args);
        }

        if ($toolName === 'get_quotation') {
            return $this->toolGetQuotation($args);
        }

        return [
            'isError' => true,
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Tool '{$toolName}' is not recognized.",
                ],
            ],
        ];
    }

    /**
     * Tool: generate_premium_quotation_pdf
     */
    private function toolGenerateQuotation(array $args): array
    {
        $projectName = trim($args['project_name'] ?? 'Custom Software Infrastructure');
        $clientName = trim($args['client_name'] ?? 'Valued Client');
        $clientBusiness = trim($args['client_business'] ?? '');
        $clientEmail = trim($args['client_email'] ?? '');
        $clientMobile = trim($args['client_mobile'] ?? '');
        $currency = strtoupper($args['currency'] ?? 'USD');
        $isUsd = $currency !== 'EGP';
        $exchangeRate = (float)($args['exchange_rate'] ?? 50.0);

        // Process platforms / pages
        $rawPlatforms = $args['platforms'] ?? [];
        $platformItems = [];
        $subtotalUsd = 0;

        foreach ($rawPlatforms as $item) {
            $title = $item['title'] ?? 'Custom Module';
            $count = max(1, (int)($item['count'] ?? 1));
            $unit = $item['unit'] ?? 'Unit';
            $unitPriceUsd = (float)($item['unit_price_usd'] ?? 10.0);
            $totalItemUsd = $count * $unitPriceUsd;

            $subtotalUsd += $totalItemUsd;

            $platformItems[] = [
                'title' => $title,
                'count' => $count,
                'unit' => $unit,
                'rate' => $unitPriceUsd,
                'rate_usd' => $unitPriceUsd,
                'cost' => $totalItemUsd,
                'total_usd' => $totalItemUsd,
                'total_egp' => round($totalItemUsd * $exchangeRate),
            ];
        }

        // Process add-ons
        $rawAddons = $args['addons'] ?? [];
        $itemizedAddons = [];

        foreach ($rawAddons as $addon) {
            $title = $addon['title'] ?? 'Feature Module';
            $priceUsd = (float)($addon['price_usd'] ?? 0);
            $subtotalUsd += $priceUsd;

            $itemizedAddons[] = [
                'title' => $title,
                'cost' => $priceUsd,
                'price_usd' => $priceUsd,
                'price_egp' => round($priceUsd * $exchangeRate),
                'desc' => $addon['description'] ?? ($addon['desc'] ?? ''),
                'description' => $addon['description'] ?? ($addon['desc'] ?? ''),
            ];
        }

        // Apply discount if provided
        $discountUsd = max(0, (float)($args['discount_usd'] ?? 0));
        $totalUsd = max(0, $subtotalUsd - $discountUsd);
        $totalEgp = round($totalUsd * $exchangeRate);

        // Executive narrative and architectural specs
        $executiveSummary = !empty($args['executive_summary']) 
            ? trim($args['executive_summary']) 
            : "This proposal establishes the technical architecture, execution roadmap, and commercial investment schedule for {$projectName}. The system is architected for enterprise stability, rapid response times, and multi-channel synchronization, ensuring high business continuity and full intellectual property ownership.";

        $architecturalApproach = !empty($args['architectural_approach'])
            ? trim($args['architectural_approach'])
            : "The system is constructed using a decoupled domain-driven architecture. User-facing interfaces communicate with an event-driven application layer with strict transactional persistence. All third-party gateways and asynchronous tasks are isolated behind resilient retry queues with automated telemetry.";

        $techStack = !empty($args['tech_stack']) && is_array($args['tech_stack'])
            ? $args['tech_stack']
            : ['Laravel 12 / PHP 8.4', 'React 18 & Inertia.js', 'Tailwind CSS v4', 'PostgreSQL / SQLite Engine', 'Real-time WebSocket Bus', 'Containerized Cloud Deploy'];

        $milestones = !empty($args['milestones']) && is_array($args['milestones'])
            ? $args['milestones']
            : [
                [
                    'phase' => 'Phase 01',
                    'title' => 'Architecture, Data Modeling & Interactive Prototype',
                    'duration' => 'Week 1 - 2',
                    'deliverables' => 'Relational database schema, API boundary contracts, and responsive UI wireframe prototypes.',
                ],
                [
                    'phase' => 'Phase 02',
                    'title' => 'Core Domain Engineering & Gateway Connectors',
                    'duration' => 'Week 3 - 4',
                    'deliverables' => 'Full business logic, payment gateway integration, automated webhooks, and administrative control panels.',
                ],
                [
                    'phase' => 'Phase 03',
                    'title' => 'Security Audit, E2E Verification & Cloud Launch',
                    'duration' => 'Week 5',
                    'deliverables' => 'Automated test suite verification, SSL / server hardening, DNS routing, and final handover.',
                ],
            ];

        // Timezone rule: Cairo timezone Africa/Cairo
        $nowCairo = now()->timezone('Africa/Cairo');
        $code = 'QT-' . $nowCairo->format('Ymd') . '-' . strtoupper(Str::random(5));

        $quoteData = [
            'code' => $code,
            'client_name' => $clientName,
            'client_business' => $clientBusiness,
            'client_email' => $clientEmail,
            'client_mobile' => $clientMobile,
            'platforms_summary' => $projectName,
            'platform_items' => $platformItems,
            'itemized_addons' => $itemizedAddons,
            'subtotal_usd' => $subtotalUsd,
            'discount_usd' => $discountUsd,
            'total_usd' => $totalUsd,
            'total_egp' => $totalEgp,
            'exchange_rate' => $exchangeRate,
            'is_usd' => $isUsd,
            'executive_summary' => $executiveSummary,
            'architectural_approach' => $architecturalApproach,
            'tech_stack' => $techStack,
            'milestones' => $milestones,
            'cairo_date' => $nowCairo->format('M d, Y - h:i A') . ' (Cairo Time)',
            'valid_until' => $nowCairo->copy()->addDays(30)->format('M d, Y'),
            'notes' => $args['notes'] ?? [],
        ];

        // Cache for 30 days
        Cache::put("quotation:{$code}", $quoteData, now()->addDays(30));

        $publicUrl = route('public.quotation.show', ['code' => $code]);
        $pdfUrl = route('public.quotation.pdf', ['code' => $code]);

        $formattedTotal = $isUsd ? '$' . number_format($totalUsd) : number_format($totalEgp) . ' EGP';

        $summaryText = "Official Executive Quotation Generated Successfully.\n\n"
            . "• Code: {$code}\n"
            . "• Client: {$clientName}\n"
            . "• Project: {$projectName}\n"
            . "• Total: {$formattedTotal}\n"
            . "• Valid Until: " . $quoteData['valid_until'] . "\n\n"
            . "View Official Online Proposal:\n{$publicUrl}\n\n"
            . "Download Pixel-Perfect PDF:\n{$pdfUrl}";

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $summaryText,
                ],
            ],
            'meta' => [
                'code' => $code,
                'public_url' => $publicUrl,
                'pdf_url' => $pdfUrl,
                'total_usd' => $totalUsd,
                'total_egp' => $totalEgp,
            ],
        ];
    }

    /**
     * Tool: get_estimator_rate_card
     */
    private function toolGetRateCard(array $args): array
    {
        $exchangeRate = (float)($args['exchange_rate'] ?? 50.0);
        $estimatorService = new ProjectEstimatorDataService();
        $data = $estimatorService->getEstimatorData($exchangeRate);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];
    }

    /**
     * Tool: get_quotation
     */
    private function toolGetQuotation(array $args): array
    {
        $code = trim($args['code'] ?? '');
        $quote = Cache::get("quotation:{$code}");

        if (!$quote) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Quotation #{$code} was not found or has expired.",
                    ],
                ],
            ];
        }

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($quote, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }

    /**
     * Tools Schema definitions.
     */
    private function getToolsDefinition(): array
    {
        return [
            [
                'name' => 'generate_premium_quotation_pdf',
                'description' => 'Generates an official corporate quotation with live links and downloadable pixel-perfect PDF. Returns proposal URL and direct PDF link.',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['project_name', 'client_name'],
                    'properties' => [
                        'project_name' => [
                            'type' => 'string',
                            'description' => 'Title or name of the project (e.g., Pepper Sauce Store, Real Estate ERP).',
                        ],
                        'client_name' => [
                            'type' => 'string',
                            'description' => 'Name of the client or recipient.',
                        ],
                        'client_business' => [
                            'type' => 'string',
                            'description' => 'Company or organization name.',
                        ],
                        'client_email' => [
                            'type' => 'string',
                            'description' => 'Client email address.',
                        ],
                        'client_mobile' => [
                            'type' => 'string',
                            'description' => 'Client phone or WhatsApp number.',
                        ],
                        'currency' => [
                            'type' => 'string',
                            'enum' => ['USD', 'EGP', 'SAR', 'AED'],
                            'default' => 'USD',
                            'description' => 'Billing currency.',
                        ],
                        'exchange_rate' => [
                            'type' => 'number',
                            'default' => 50.0,
                            'description' => 'Exchange rate for EGP conversion.',
                        ],
                        'platforms' => [
                            'type' => 'array',
                            'description' => 'List of platform scope items (web pages, mobile screens, desktop screens).',
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'count', 'unit_price_usd'],
                                'properties' => [
                                    'title' => ['type' => 'string', 'example' => 'Website Frontend & Checkout'],
                                    'count' => ['type' => 'integer', 'example' => 5],
                                    'unit' => ['type' => 'string', 'example' => 'Page'],
                                    'unit_price_usd' => ['type' => 'number', 'example' => 10.0],
                                ],
                            ],
                        ],
                        'addons' => [
                            'type' => 'array',
                            'description' => 'List of add-on modules (Admin panel, payment gateway, SEO pack, etc.).',
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'price_usd'],
                                'properties' => [
                                    'title' => ['type' => 'string', 'example' => 'Stripe & PayPal Gateway'],
                                    'price_usd' => ['type' => 'number', 'example' => 50.0],
                                    'description' => ['type' => 'string', 'example' => 'Complete multi-currency integration'],
                                ],
                            ],
                        ],
                        'discount_usd' => [
                            'type' => 'number',
                            'default' => 0,
                            'description' => 'Discount amount in USD.',
                        ],
                        'executive_summary' => [
                            'type' => 'string',
                            'description' => 'High-level business brief and strategic objectives of the project.',
                        ],
                        'architectural_approach' => [
                            'type' => 'string',
                            'description' => 'Technical blueprint, architectural layer patterns, and engineering methodology.',
                        ],
                        'tech_stack' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Technologies and tools utilized in the architecture.',
                        ],
                        'milestones' => [
                            'type' => 'array',
                            'description' => 'Implementation phases and sprint roadmap.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'phase' => ['type' => 'string'],
                                    'title' => ['type' => 'string'],
                                    'duration' => ['type' => 'string'],
                                    'deliverables' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'notes' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Special notes, SLA, or milestones to include in the quotation.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_estimator_rate_card',
                'description' => 'Retrieve official Musoftware base rates for websites, mobile apps, desktop systems, and addons.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'exchange_rate' => [
                            'type' => 'number',
                            'default' => 50.0,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_quotation',
                'description' => 'Look up an existing official quotation by its unique code (e.g. QT-20260912-ABCDE).',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['code'],
                    'properties' => [
                        'code' => [
                            'type' => 'string',
                            'description' => 'The quotation code.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
