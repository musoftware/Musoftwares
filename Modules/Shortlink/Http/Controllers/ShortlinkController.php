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
            'title' => $link->title,
            'description' => $link->description,
            'image_url' => $link->image_url,
            'effective_title' => $link->getEffectiveTitle(),
            'effective_description' => $link->getEffectiveDescription(),
            'effective_image' => $link->getEffectiveImage(),
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
                'seo_preview_title' => __('shortlink.seo_preview_title', [], 'ar') ?: 'بيانات ومعاينة الرابط (SEO & Social Preview)',
                'seo_title' => __('shortlink.seo_title', [], 'ar') ?: 'عنوان المعاينة (Title)',
                'seo_description' => __('shortlink.seo_description', [], 'ar') ?: 'وصف المعاينة (Description)',
                'seo_image' => __('shortlink.seo_image', [], 'ar') ?: 'رابط صورة المعاينة (Image URL)',
                'fetch_meta' => __('shortlink.fetch_meta', [], 'ar') ?: 'جلب البيانات تلقائياً من الرابط',
                'fetching_meta' => __('shortlink.fetching_meta', [], 'ar') ?: 'جاري الجلب...',
                'preview_card_heading' => __('shortlink.preview_card_heading', [], 'ar') ?: 'معاينة المشاركة (WhatsApp / Social Card)',
            ],
        ]);
    }

    public function fetchMeta(Request $request)
    {
        $this->authorize('viewAny', ShortlinkLink::class);

        $request->validate([
            'url' => ['required', 'string', 'url', 'max:5000'],
        ]);

        $meta = $this->service->fetchUrlMetadata($request->input('url'));

        return response()->json($meta);
    }

    public function store(StoreShortlinkRequest $request)
    {
        $this->authorize('create', ShortlinkLink::class);

        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()?->id;

        if (empty($data['title']) && empty($data['description']) && empty($data['image_url'])) {
            $meta = $this->service->fetchUrlMetadata($data['destination_url']);
            $data['title'] = $meta['title'] ?? null;
            $data['description'] = $meta['description'] ?? null;
            $data['image_url'] = $meta['image_url'] ?? null;
        }

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
