<?php

namespace Modules\Shortlink\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shortlink\Models\ShortlinkLink;
use Modules\Shortlink\Services\ShortlinkService;

class ShortlinkRedirectController extends Controller
{
    public function __construct(private ShortlinkService $service)
    {
    }

    /**
     * Public redirect: /l/{code} -> 302 to the stored destination.
     *
     * The short code is the access credential (bearer token), so the lookup
     * intentionally distinguishes the failure modes:
     *  - unknown code      -> 404
     *  - inactive / trashed / expired -> 410 Gone
     */
    public function redirect(Request $request, string $code)
    {
        $link = ShortlinkLink::withTrashed()
            ->where('short_code', $code)
            ->first();

        if (!$link) {
            abort(404);
        }

        if ($link->trashed() || !$link->is_active || $link->isExpired()) {
            abort(410);
        }

        $this->service->recordClick($link);

        // If request is from a social preview crawler (WhatsApp, Facebook, Twitter, Telegram, etc.)
        if ($this->service->isCrawler($request) || $request->query('preview') === '1') {
            // Auto-enrich metadata if not present
            if (empty($link->title) || empty($link->description) || empty($link->image_url)) {
                $meta = $this->service->fetchUrlMetadata($link->destination_url);
                $updated = false;
                if (empty($link->title) && !empty($meta['title'])) {
                    $link->title = $meta['title'];
                    $updated = true;
                }
                if (empty($link->description) && !empty($meta['description'])) {
                    $link->description = $meta['description'];
                    $updated = true;
                }
                if (empty($link->image_url) && !empty($meta['image_url'])) {
                    $link->image_url = $meta['image_url'];
                    $updated = true;
                }
                if ($updated) {
                    $link->save();
                }
            }

            return response()->view('shortlink-preview', [
                'title' => $link->getEffectiveTitle(),
                'description' => $link->getEffectiveDescription(),
                'image' => $link->getEffectiveImage(),
                'url' => $this->service->shortUrl($link),
                'destination_url' => $link->destination_url,
            ])->header('Cache-Control', 'public, max-age=300');
        }

        return redirect()->away($link->destination_url, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
