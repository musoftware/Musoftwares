<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Single-use password reset flow issued by an admin (or by the create-user
 * co-worker flow). The URL itself is a Laravel signed URL (HMAC over the
 * query string) AND embeds a random token; both must validate. The token is
 * stored in the cache and atomically consumed on successful submission.
 *
 * Routes:
 *   GET  /auth/set-password?token=…   → renders a "set your password" form
 *   POST /auth/set-password            → consumes the token and sets the password
 */
class SetPasswordController extends Controller
{
    private const CACHE_PREFIX = 'admin_password_set:';

    private const TTL_HOURS = 24;

    public function create(Request $request): InertiaResponse
    {
        $this->assertValidRequest($request);

        $user = $this->resolveUser($request);

        return Inertia::render('Auth/SetPassword', [
            'name' => $user->name,
            'email' => $user->email,
            'token' => $request->query('token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertValidRequest($request);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $token = (string) $request->query('token');
        $cacheKey = self::CACHE_PREFIX.$token;

        // Atomic single-use consumption: pull-and-delete from cache in one step.
        $payload = Cache::pull($cacheKey);

        if (! is_array($payload) || empty($payload['user_id']) || empty($payload['expires_at'])) {
            throw ValidationException::withMessages([
                'password' => __('This link has expired or already been used. Please ask an administrator for a new one.'),
            ]);
        }

        if (now()->greaterThan($payload['expires_at'])) {
            throw ValidationException::withMessages([
                'password' => __('This link has expired. Please ask an administrator for a new one.'),
            ]);
        }

        $user = User::find($payload['user_id']);
        if (! $user || (string) $user->id !== (string) $request->query('uid')) {
            throw ValidationException::withMessages([
                'password' => __('This link is no longer valid.'),
            ]);
        }

        DB::transaction(function () use ($user, $data) {
            $user->password = Hash::make($data['password']);
            $user->setRememberToken(Str::random(60));
            // Force logout from any other session.
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            $user->save();
        });

        app(AdminAuditService::class)->record(
            'user.password_set_via_admin_link',
            $user,
            [
                'issued_by' => $payload['issued_by'] ?? null,
            ],
            AdminAuditLog::SEVERITY_WARNING
        );

        return redirect()->route('login')->with('status', __('Your password has been set. You can now log in.'));
    }

    /**
     * Issue a one-time signed URL for $user. The token in the URL is a random
     * 32-byte string; the URL is signed by Laravel's URL signer (so an attacker
     * cannot construct or tamper with it).
     */
    public static function issueLink(User $user, ?int $issuedBy = null): string
    {
        $token = Str::random(48);

        Cache::put(self::CACHE_PREFIX.$token, [
            'user_id' => $user->id,
            'issued_by' => $issuedBy,
            'expires_at' => now()->addHours(self::TTL_HOURS),
            'ip' => request()->ip(),
        ], now()->addHours(self::TTL_HOURS));

        return URL::temporarySignedRoute(
            'password.set.show',
            now()->addHours(self::TTL_HOURS),
            ['token' => $token, 'uid' => $user->id]
        );
    }

    private function assertValidRequest(Request $request): void
    {
        if (! $request->hasValidSignature()) {
            abort(403, __('This link is invalid or has expired.'));
        }

        if (! $request->filled('token') || ! $request->filled('uid')) {
            abort(403, __('This link is invalid or has expired.'));
        }
    }

    private function resolveUser(Request $request): User
    {
        $user = User::find($request->query('uid'));
        if (! $user) {
            abort(404, __('User not found.'));
        }

        return $user;
    }
}
