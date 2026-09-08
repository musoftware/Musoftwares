<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialSoftware;
use App\Models\StoreTool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreToolController extends Controller
{
    /**
     * Display a listing of the store tools for Admin.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all'); // all, published, draft
        $type = $request->input('type', 'all');     // all, free, paid

        $query = StoreTool::query()->with('serialSoftware:id,name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('tagline', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        if ($type === 'free') {
            $query->where(function ($q) {
                $q->where('requires_payment', false)->orWhere('price', '<=', 0);
            });
        } elseif ($type === 'paid') {
            $query->where('requires_payment', true)->where('price', '>', 0);
        }

        $tools = $query->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_tools'      => StoreTool::count(),
            'published_tools'  => StoreTool::where('is_published', true)->count(),
            'draft_tools'      => StoreTool::where('is_published', false)->count(),
            'paid_tools'       => StoreTool::where('requires_payment', true)->where('price', '>', 0)->count(),
            'linked_softwares' => StoreTool::whereNotNull('serial_software_id')->count(),
        ];

        $serialSoftwares = SerialSoftware::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/StoreTools/Index', [
            'tools'           => $tools,
            'filters'         => [
                'search' => $search,
                'status' => $status,
                'type'   => $type,
            ],
            'stats'           => $stats,
            'serialSoftwares' => $serialSoftwares,
        ]);
    }

    /**
     * Store a newly created tool in the store.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTool($request);

        StoreTool::create($validated);

        return back()->with('success', __('Tool added to store catalog successfully.'));
    }

    /**
     * Update the specified store tool.
     */
    public function update(Request $request, StoreTool $storeTool): RedirectResponse
    {
        $validated = $this->validateTool($request, $storeTool->id);

        $storeTool->update($validated);

        return back()->with('success', __('Store tool updated successfully.'));
    }

    /**
     * Toggle published status of a store tool.
     */
    public function toggleStatus(StoreTool $storeTool): RedirectResponse
    {
        $storeTool->update([
            'is_published' => ! $storeTool->is_published,
        ]);

        $statusMsg = $storeTool->is_published ? __('published to store') : __('moved to drafts');

        return back()->with('success', __('Tool is now :status.', ['status' => $statusMsg]));
    }

    /**
     * Remove (soft-delete) the specified store tool.
     */
    public function destroy(StoreTool $storeTool): RedirectResponse
    {
        $storeTool->delete();

        return back()->with('success', __('Tool removed from store successfully.'));
    }

    /**
     * Shared validation rules for store & update.
     */
    protected function validateTool(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'tagline'              => ['nullable', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:10000'],
            'version'              => ['nullable', 'string', 'max:50'],
            'download_url'         => ['nullable', 'url', 'max:500'],
            'category'             => ['nullable', 'string', 'max:100'],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'currency'             => ['nullable', 'string', 'max:10'],
            'requires_payment'     => ['nullable', 'boolean'],
            'whatsapp_number'      => ['nullable', 'string', 'max:50'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
            'features'             => ['nullable', 'array'],
            'features.*'           => ['string', 'max:255'],
            'serial_software_id'   => ['nullable', 'exists:serial_softwares,id'],
            'is_published'         => ['nullable', 'boolean'],
            'sort_order'           => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['requires_payment'] = (bool) ($validated['requires_payment'] ?? false);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? true);
        $validated['price'] = ! empty($validated['price']) ? (float) $validated['price'] : 0.00;
        $validated['currency'] = ! empty($validated['currency']) ? strtoupper(trim($validated['currency'])) : 'USD';
        $validated['sort_order'] = ! empty($validated['sort_order']) ? (int) $validated['sort_order'] : 0;

        return $validated;
    }
}
