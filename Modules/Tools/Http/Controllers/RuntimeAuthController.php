<?php

namespace Modules\Tools\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

/**
 * RuntimeAuthController
 * ─────────────────────────────────────────────────────────────
 * Handles the browser-based device login handshake.
 *
 * Flow:
 *   1. Runtime opens browser → GET /runtime/connect?code=XXXX&port=18400
 *   2. If not logged in → redirect to /login?intended=/runtime/connect?code=...
 *   3. GET /runtime/connect → show "Connect Musoftware Runtime" confirmation page
 *   4. User clicks "Allow" → POST /runtime/connect
 *   5. Controller creates a Sanctum token, POSTs it to runtime's /auth/callback
 *   6. Redirect with success message or error
 */
class RuntimeAuthController extends Controller
{
    /**
     * GET /runtime/connect?code=XXXX&port=18400
     * Show the confirmation page before granting access.
     */
    public function connect(Request $request)
    {
        $code = $request->query('code');
        $port = (int) $request->query('port', 18400);
        $success = $request->query('success') == '1';

        if (! $code) {
            return Inertia::render('Tools/RuntimeConnect', [
                'missingCode' => true,
                'code'        => '',
                'port'        => $port,
                'userName'    => auth()->user()->name,
                'userEmail'   => auth()->user()->email,
                'success'     => $success,
            ]);
        }

        return Inertia::render('Tools/RuntimeConnect', [
            'code'      => $code,
            'port'      => $port,
            'userName'  => auth()->user()->name,
            'userEmail' => auth()->user()->email,
            'success'   => $success,
        ]);
    }

    /**
     * POST /runtime/connect
     * Create token and push it back to the local runtime.
     */
    public function approve(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:10',
            'port' => 'required|integer|min:1024|max:65535',
        ]);

        $code = $request->input('code');
        $port = (int) $request->input('port');
        $user = auth()->user();

        // Create a Sanctum token scoped to the runtime
        $token = $user->createToken(
            name:       'musoftware-runtime',
            abilities:  ['runtime:sync', 'runtime:plugins'],
            expiresAt:  null, // long-lived — user can revoke from /settings/tokens
        );

        return response()->json([
            'token'    => $token->plainTextToken,
            'userId'   => (string) $user->id,
            'userName' => $user->name,
        ]);
    }
}
