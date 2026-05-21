<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\ToolReseller;
use Modules\Tools\Models\ToolResellerUser;

class ResellerPortalController extends Controller
{
    /**
     * Render the reseller portal — a full tools UI scoped to this reseller.
     * The token in the URL identifies which reseller this portal belongs to.
     * Sub-users register/login via this URL and are automatically linked to the reseller.
     */
    public function show(string $token): Response
    {
        $reseller = ToolReseller::where('token', $token)->firstOrFail();

        if ($reseller->status !== 'active') {
            return Inertia::render('Tools/ResellerSuspended', [
                'reseller_name' => $reseller->name,
                'reason'        => $reseller->status,
            ]);
        }

        // Store reseller token in session so registration/login links this user
        session(['reseller_token' => $token]);

        // If user is already authenticated, ensure they're linked to this reseller
        if (auth()->check()) {
            $this->ensureUserLinkedToReseller(auth()->id(), $reseller);
        }

        // Load tools catalog for the portal
        $tools = collect(config('tools'))
            ->filter(fn ($t) => $t['is_active'] ?? false)
            ->values()
            ->map(fn ($t) => [
                'slug'              => $t['slug'],
                'guid'              => $t['guid'],
                'title'             => $t['title'],
                'short_description' => $t['short_description'] ?? '',
                'category'          => $t['category'] ?? 'General',
                'icon_url'          => $t['icon_url'] ?? null,
                'is_free'           => $t['is_free'] ?? false,
                'plans'             => collect($t['plans'] ?? [])->map(fn ($p) => [
                    'guid'          => $p['guid'],
                    'name'          => $p['name'],
                    'price_monthly' => $p['price_monthly'],
                    'price_yearly'  => $p['price_yearly'],
                ])->values(),
            ]);

        return Inertia::render('Tools/ResellerPortal', [
            'reseller'      => [
                'name'     => $reseller->name,
                'token'    => $reseller->token,
                'currency' => $reseller->currency,
            ],
            'tools'         => $tools,
            'isAuthenticated' => auth()->check(),
        ]);
    }

    /**
     * Link an authenticated user to the reseller if not already linked.
     */
    private function ensureUserLinkedToReseller(int $userId, ToolReseller $reseller): void
    {
        ToolResellerUser::firstOrCreate(
            ['reseller_id' => $reseller->id, 'user_id' => $userId],
            ['status' => 'active', 'joined_at' => now()]
        );
    }
}
