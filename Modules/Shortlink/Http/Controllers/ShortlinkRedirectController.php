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

        return redirect()->away($link->destination_url, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
