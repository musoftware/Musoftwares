<?php

namespace Modules\Shortlink\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Shortlink\Http\Requests\StoreShortlinkRequest;
use Modules\Shortlink\Models\ShortlinkLink;
use Modules\Shortlink\Services\ShortlinkService;

class ShortlinkController extends Controller
{
    public function __construct(private ShortlinkService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ShortlinkLink::class);

        $query = ShortlinkLink::query()
            ->with('creator')
            ->latest();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('short_code', 'like', "%{$search}%")
                    ->orWhere('destination_url', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $links = $query->paginate(20)->through(fn (ShortlinkLink $link) => [
            'id' => $link->id,
            'short_code' => $link->short_code,
            'short_url' => $this->service->shortUrl($link),
            'destination_url' => $link->destination_url,
            'label' => $link->label,
            'is_active' => (bool) $link->is_active,
            'clicks' => (int) $link->clicks,
            'expires_at' => optional($link->expires_at)->toIso8601String(),
            'created_at' => $link->created_at?->toIso8601String(),
            'source_type' => $link->source_type,
            'creator' => $link->creator ? [
                'id' => $link->creator->id,
                'name' => $link->creator->name,
            ] : null,
        ]);

        return Inertia::render('Admin/Shortlinks/Index', [
            'links' => $links,
            'filters' => $request->only(['q']),
            'translations' => [
                'title' => __('shortlink.title'),
                'subtitle' => __('shortlink.subtitle'),
                'short_code' => __('shortlink.short_code'),
                'short_url' => __('shortlink.short_url'),
                'destination_url' => __('shortlink.destination_url'),
                'label' => __('shortlink.label'),
                'clicks' => __('shortlink.clicks'),
                'status' => __('shortlink.status'),
                'active' => __('shortlink.active'),
                'inactive' => __('shortlink.inactive'),
                'expires_at' => __('shortlink.expires_at'),
                'never' => __('shortlink.never'),
                'created_at' => __('shortlink.created_at'),
                'created_by' => __('shortlink.created_by'),
                'actions' => __('shortlink.actions'),
                'no_links' => __('shortlink.no_links'),
                'create_new' => __('shortlink.create_new'),
                'destination_url_placeholder' => __('shortlink.destination_url_placeholder'),
                'label_placeholder' => __('shortlink.label_placeholder'),
                'expires_at_placeholder' => __('shortlink.expires_at_placeholder'),
                'submit' => __('shortlink.submit'),
                'cancel' => __('shortlink.cancel'),
                'copy' => __('shortlink.copy'),
                'copied' => __('shortlink.copied'),
                'open' => __('shortlink.open'),
                'delete' => __('shortlink.delete'),
                'toggle_status' => __('shortlink.toggle_status'),
                'confirm_delete' => __('shortlink.confirm_delete'),
                'search_placeholder' => __('shortlink.search_placeholder'),
            ],
        ]);
    }

    public function store(StoreShortlinkRequest $request)
    {
        $this->authorize('create', ShortlinkLink::class);

        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()?->id;

        $link = $this->service->create($data);

        return back()->with('success', __('shortlink.created_successfully'));
    }

    public function toggle(Request $request, ShortlinkLink $shortlink)
    {
        $this->authorize('update', $shortlink);

        $shortlink->update(['is_active' => !$shortlink->is_active]);

        return back()->with('success', __('shortlink.status_updated_successfully'));
    }

    public function destroy(Request $request, ShortlinkLink $shortlink)
    {
        $this->authorize('delete', $shortlink);

        $shortlink->delete();

        return back()->with('success', __('shortlink.deleted_successfully'));
    }
}
