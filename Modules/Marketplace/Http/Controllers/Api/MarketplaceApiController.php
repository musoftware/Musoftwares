<?php

namespace Modules\Marketplace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketplaceApiController extends Controller
{
    /**
     * Get paginated services list.
     */
    public function services(Request $request): JsonResponse
    {
        $query = Service::with(['category', 'packages', 'seller'])
            ->whereNull('suspended_at');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min((int)$request->get('per_page', 15), 50);
        $services = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $services->items(),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ]
        ]);
    }

    /**
     * Get list of categories.
     */
    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::withCount(['services' => function ($q) {
            $q->whereNull('suspended_at');
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Full Text & Filtered Search via SQL LIKE.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Service::with(['category', 'packages', 'seller'])
            ->whereNull('suspended_at');

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', $search)
                  ->orWhere('tagline', 'LIKE', $search)
                  ->orWhere('description', 'LIKE', $search);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('tag')) {
            $tag = trim($request->tag);
            $query->whereJsonContains('tags', $tag);
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $min = (float)($request->min_price ?? 0);
            $max = (float)($request->max_price ?? 999999);

            $query->whereHas('packages', function ($q) use ($min, $max) {
                $q->whereBetween('price', [$min, $max]);
            });
        }

        $perPage = min((int)$request->get('per_page', 15), 50);
        $results = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'query' => $request->q ?? '',
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ]
        ]);
    }

    /**
     * Autocomplete suggestions endpoint.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        if (empty($term)) {
            return response()->json(['status' => 'success', 'suggestions' => []]);
        }

        $services = Service::whereNull('suspended_at')
            ->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', '%' . $term . '%')
                  ->orWhere('tagline', 'LIKE', '%' . $term . '%');
            })
            ->select(['id', 'title', 'slug', 'category_id', 'thumbnail'])
            ->with(['category:id,name'])
            ->limit(8)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'slug' => $s->slug,
                    'category' => $s->category->name ?? '',
                    'thumbnail' => $s->thumbnail,
                    'url' => route('marketplace.services.show', ['id' => $s->id, 'slug' => $s->slug]),
                ];
            });

        return response()->json([
            'status' => 'success',
            'suggestions' => $services,
        ]);
    }

    /**
     * Get pricing structure for a service.
     */
    public function pricing(Request $request): JsonResponse
    {
        $serviceId = $request->get('service_id');
        if (!$serviceId) {
            return response()->json(['status' => 'error', 'message' => 'service_id parameter is required.'], 422);
        }

        $service = Service::with(['packages', 'extras'])->find($serviceId);
        if (!$service) {
            return response()->json(['status' => 'error', 'message' => 'Service not found.'], 440);
        }

        return response()->json([
            'status' => 'success',
            'service_id' => $service->id,
            'service_title' => $service->title,
            'packages' => $service->packages,
            'extras' => $service->extras,
        ]);
    }

    /**
     * Create an order via API (Authenticated).
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:marketplace_services,id',
            'package_id' => 'required|exists:marketplace_service_packages,id',
            'requirements' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $package = ServicePackage::findOrFail($request->package_id);

        try {
            DB::beginTransaction();

            $order = ServiceOrder::create([
                'buyer_id' => $user->id,
                'package_id' => $package->id,
                'price' => $package->price,
                'amount' => $package->price,
                'delivery_days' => $package->delivery_days ?? 1,
                'status' => 'pending',
                'requirements_submitted' => !empty($request->requirements),
                'requirements_data' => $request->requirements ?? [],
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully.',
                'order' => [
                    'id' => $order->id,
                    'price' => $order->price,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get status of an order (Authenticated).
     */
    public function orderStatus(string $orderId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $order = ServiceOrder::with(['package.service', 'history', 'deliveries'])
            ->where('id', $orderId)
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhereHas('package.service', function ($sq) use ($user) {
                      $sq->where('seller_id', $user->id);
                  });
            })
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found or unauthorized.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'price' => $order->price,
                'delivery_days' => $order->delivery_days,
                'created_at' => $order->created_at->toIso8601String(),
                'history' => $order->history,
                'deliveries' => $order->deliveries,
            ]
        ]);
    }

    /**
     * OpenAPI 3.0 Specification JSON.
     */
    public function openapi(): JsonResponse
    {
        $baseUrl = config('app.url', 'https://musoftwares.com');

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Musoftwares Marketplace API',
                'version' => '1.0.0',
                'description' => 'Machine readable APIs for searching, reading, pricing, and placing orders on Musoftwares Marketplace.',
                'contact' => [
                    'email' => 'support@musoftwares.com',
                    'url' => $baseUrl,
                ]
            ],
            'servers' => [
                ['url' => $baseUrl . '/marketplace/api/v1', 'description' => 'Production API Server']
            ],
            'paths' => [
                '/services' => [
                    'get' => [
                        'summary' => 'List Services',
                        'parameters' => [
                            ['name' => 'category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                            ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 15]],
                        ],
                        'responses' => ['200' => ['description' => 'Successful response']]
                    ]
                ],
                '/categories' => [
                    'get' => [
                        'summary' => 'List Categories',
                        'responses' => ['200' => ['description' => 'Successful response']]
                    ]
                ],
                '/search' => [
                    'get' => [
                        'summary' => 'Search Services',
                        'parameters' => [
                            ['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                            ['name' => 'tag', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'min_price', 'in' => 'query', 'schema' => ['type' => 'number']],
                            ['name' => 'max_price', 'in' => 'query', 'schema' => ['type' => 'number']],
                        ],
                        'responses' => ['200' => ['description' => 'Successful response']]
                    ]
                ],
                '/search/autocomplete' => [
                    'get' => [
                        'summary' => 'Autocomplete Search Suggestions',
                        'parameters' => [
                            ['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string']]
                        ],
                        'responses' => ['200' => ['description' => 'Successful response']]
                    ]
                ],
                '/pricing' => [
                    'get' => [
                        'summary' => 'Service Pricing Details',
                        'parameters' => [
                            ['name' => 'service_id', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']]
                        ],
                        'responses' => ['200' => ['description' => 'Successful response']]
                    ]
                ],
                '/orders' => [
                    'post' => [
                        'summary' => 'Create Order',
                        'security' => [['bearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'service_id' => ['type' => 'integer'],
                                            'package_id' => ['type' => 'integer'],
                                            'requirements' => ['type' => 'object'],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => ['201' => ['description' => 'Order created']]
                    ]
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum Token'
                    ]
                ]
            ]
        ];

        return response()->json($spec);
    }
}
