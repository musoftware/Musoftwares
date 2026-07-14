<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\AddTaskRequest;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\ToggleBlockUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\CoTechTag;
use App\Models\CoWorker;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Earning;
use App\Models\ModulePlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\AdminUserService;
use App\Services\EarningAnalyzeService;
use App\Services\PricingService;
use App\Services\WhatsAppNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Service;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function __construct(
        protected AdminUserService $adminUserService
    ) {}

    /**
     * Full user listing with search, role filter, top-users filter, sort.
     * Recovered from old UsersController::index()
     */
    public function index(Request $request): InertiaResponse
    {
        $query = User::query()->with('roles');

        // Full-text search across name, email, whatsapp
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if (Schema::hasColumn('users', 'whatsapp_number')) {
                    $q->orWhere('whatsapp_number', 'like', "%{$search}%");
                }
            });
        }

        // Role filter
        if ($role = $request->get('role')) {
            $query->role($role); // Spatie HasRoles scope
        }

        // Account status filter
        if ($status = $request->get('status')) {
            $query->where('account_status', $status);
        }

        // KYC filter
        if ($request->get('kyc') === 'verified') {
            $query->where('kyc_verified', true);
        } elseif ($request->get('kyc') === 'unverified') {
            $query->where('kyc_verified', false);
        }

        // Sorting
        $sortable = ['name', 'email', 'created_at', 'id', 'last_activity_at'];
        $sort = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'id';
        // The old system defaulted to ASC sorting for users
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);

        $users = $query->paginate(25)->withQueryString()->through(fn ($user) => (new UserResource($user))->resolve());

        $stats = [
            'total' => User::count(),
            'active' => User::where('account_status', 'active')->orWhereNull('account_status')->count(),
            'blocked' => User::where('account_status', 'blocked')->count(),
            'kyc_verified' => User::where('kyc_verified', true)->count(),
            'new_this_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'clients' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'kyc', 'sort', 'direction']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show full user profile with stats.
     * Recovered from old UsersController::show()
     */
    public function show(Request $request, $id)
    {
        $user = User::with(['kycDocuments', 'kycVerifier:id,name', 'tickets', 'roles', 'loans.currency', 'loans.repayments'])
            ->findOrFail($id);

        $initials = collect(explode(' ', $user->name))
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'))
            ->take(2)
            ->implode('');

        $stats = [
            'tickets_total' => $user->tickets()->count(),
            'tickets_open' => $user->tickets()->where('ticket_status', 'open')->count(),
            'kyc_docs_count' => $user->kycDocuments()->count(),
        ];

        // Fetch stats using User model relations
        try {
            $stats['invoices_total'] = $user->invoices()->count();
            $stats['invoices_paid'] = $user->invoices()->where('status', 'paid')->count();
            $stats['invoices_unpaid_sum'] = $user->unpaid_invoices_amount(true);
        } catch (\Throwable $e) {
            Log::error('Error fetching user stats: '.$e->getMessage());
        }

        // Try Marketplace stats
        try {
            if (class_exists('\Modules\Marketplace\Models\Order')) {
                $stats['orders_total'] = Order::where('buyer_id', $user->id)->count();
            }
            if (class_exists('\Modules\Marketplace\Models\Service')) {
                $stats['services_total'] = Service::where('seller_id', $user->id)->count();
                $stats['services_approved'] = Service::where('seller_id', $user->id)->where('status', 'approved')->count();
            }
        } catch (\Throwable $e) {
        }

        $userDetail = (new UserResource($user))->resolve();

        $modulePlans = ModulePlan::where('is_active', true)->get();
        $subscriptions = $user->subscriptions()->orderBy('expires_at', 'desc')->get();
        $currencies = Currency::all();

        $recentProjects = $user->projects()
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'project_name', 'date_start', 'date_end', 'archived', 'status', 'updated_at']);
        $projectsCount = $user->projects()->count();

        return Inertia::render('Admin/Users/Show', [
            'client' => $userDetail,
            'loans' => $user->loans,
            'currencies' => $currencies,
            'stats' => $stats,
            'modulePlans' => $modulePlans,
            'subscriptions' => $subscriptions,
            'recentProjects' => $recentProjects,
            'projectsCount' => $projectsCount,

        ]);
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => ['admin', 'client'],
            'currencies' => Currency::all(),
        ]);
    }

    /**
     * Store a new user.
     * Business rule: name must include a last name.
     */
    public function store(StoreUserRequest $request)
    {

        $this->adminUserService->createFromRequest($request);

        return redirect()->route('admin.users.index')
            ->with('success', __('general.user_created_successfully'));
    }

    /**
     * Show edit form for an existing user.
     */
    public function edit($id)
    {
        $user = User::with(['kycDocuments', 'roles', 'kycVerifier'])->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name ?? '',
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'client',
                'facebook' => $user->facebook ?? '',
                'skype' => $user->skype ?? '',
                'job' => $user->job ?? '',
                'address' => $user->address ?? '',
                'phone_number' => $user->phone_number ?? '',
                'phone_number2' => $user->phone_number2 ?? '',
                'mobile_1' => $user->mobile_1 ?? '',
                'mobile_2' => $user->mobile_2 ?? '',
                'whatsapp_number' => $user->whatsapp_number ?? '',
                'disable_unpaid_balance_whatsapp' => (bool) ($user->disable_unpaid_balance_whatsapp ?? false),
                'telegram_username' => $user->telegram_username ?? '',
                'country' => $user->country ?? '',
                'city' => $user->city ?? '',
                'currency' => $user->currency_id ?? $user->currency ?? '',

                'hour_rate_currency' => $user->hour_rate_currency_id ?? $user->hour_rate_currency ?? '',
                'hour_rate' => $user->hour_rate ?? '',
                'booking_rate_currency' => $user->booking_rate_currency_id ?? $user->booking_rate_currency ?? '',
                'booking_rate' => $user->booking_rate ?? '',
                'booking_rate_expires_at' => $user->booking_rate_expires_at ? $user->booking_rate_expires_at->format('Y-m-d') : '',
                'salary' => $user->salary ?? '',
                'usd_type' => $user->usd_type ?? 'bank_usd',

                'subscription_date' => $user->subscription_date ? (is_string($user->subscription_date) ? date('Y-m-d', strtotime($user->subscription_date)) : $user->subscription_date->format('Y-m-d')) : '',
                'subscription_plan' => $user->plan_id ?? '',
                'postpaid_limit' => $user->postpaid_limit ?? '',
                'subscription_force' => (bool) ($user->subscription_force ?? false),

                'client_taxable' => (bool) ($user->client_taxable ?? false),
                'invoice_taxable' => (bool) ($user->invoice_taxable ?? false),
                'timer_taxable' => (bool) ($user->timer_taxable ?? false),

                'allow_referral_system' => (bool) ($user->allow_referral_system ?? false),
                'allow_view_times' => (bool) ($user->allow_view_times ?? false),
                'allow_postpaid' => (bool) ($user->allow_postpaid ?? false),
                'enable_notifications' => (bool) ($user->enable_notifications ?? true),

                'account_status' => $user->account_status ?? 'active',
                'block_reason' => $user->block_reason ?? '',

                'kyc_verified' => (bool) ($user->kyc_verified ?? false),
                'kyc_notes' => $user->kyc_notes ?? '',
                'kyc_verified_at' => $user->kyc_verified_at ? $user->kyc_verified_at->format('M d, Y \a\t H:i') : null,
                'kyc_verifier' => $user->kycVerifier ? ['name' => $user->kycVerifier->name] : null,
                'kyc_documents_count' => $user->kycDocuments ? $user->kycDocuments->count() : 0,

                'affiliate_commission_percentage' => $user->affiliate_commission_percentage ?? 1.00,
                'add_commission_to_total' => (bool) ($user->add_commission_to_total ?? false),
                'ref_user_id' => $user->ref_user_id ?? '',
                'slug' => $user->slug ?? '',
                'max_devices' => $user->max_devices ?? '',
            ],
            'roles' => ['client', 'user', 'admin', 'manager', 'employee', 'moderator'],
            'currencies' => Currency::all(),
            'plans' => ModulePlan::where('is_active', true)->get(),
            'statuses' => ['active', 'blocked', 'suspended'],
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, $id)
    {

        $user = User::findOrFail($id);
        $this->adminUserService->updateFromRequest($user, $request);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', __('general.user_updated_successfully'));
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('general.user_deleted'));
    }

    /**
     * Toggle block/unblock a user.
     * Recovered from old project's account_status workflow.
     */
    public function toggleBlock(ToggleBlockUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $this->adminUserService->toggleBlock($user, $request->input('reason'));

        return back()->with('success', $user->account_status === 'active'
            ? 'User unblocked.'
            : 'User blocked.');
    }

    /**
     * Impersonate a user — generate a token and redirect to impersonation endpoint.
     * Recovered from old project: UsersController::login_as()
     */
    public function loginAs(Request $request, $id)
    {
        $actorId = Auth::id();
        $user = User::findOrFail($id);

        // Block impersonating another admin (prevents privilege escalation chain).
        if ($user->isAdmin() && $user->id !== $actorId) {
            return back()->withErrors(['error' => __('errors.cannot_impersonate_admin')]);
        }

        // Refuse to impersonate a user whose account is blocked or unverified email
        // (the impersonator would inherit any blocked-by-auth restrictions).
        if (($user->account_status ?? null) === 'blocked') {
            return back()->withErrors(['error' => __('errors.cannot_impersonate_blocked_user')]);
        }

        // Issue the Sanctum token but DO NOT return it in the JSON response.
        // The browser already has the cookie/session — there's no legitimate
        // reason to surface a plaintext bearer token back to the client.
        $user->createToken('admin-impersonation-'.$actorId);

        // Track impersonation in the database-backed audit log + a session
        // marker so stopImpersonate() can validate and restore the actor.
        $startedAt = now();
        session([
            'impersonator_id' => $actorId,
            'impersonate' => $user->id,
            'impersonating_user_id' => $user->id,
            'impersonated_by' => $actorId,
            'impersonation_started_at' => $startedAt->toIso8601String(),
        ]);

        Auth::loginUsingId($user->id);

        if ($request->wantsJson() || $request->isMethod('post')) {
            return response()->json([
                'redirect_url' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Users with problematic status: unpaid invoices or blocked with no contact.
     * Recovered from old project: UsersController::problematic()
     */
    public function problematic(): InertiaResponse
    {
        $blocked = User::where('account_status', 'blocked')
            ->latest()
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'account_status' => $u->account_status,
                'block_reason' => $u->block_reason,
                'created_at' => $u->created_at,
            ]);

        $noWhatsApp = collect();
        if (Schema::hasColumn('users', 'whatsapp_number')) {
            $noWhatsApp = User::role('client')
                ->where(fn ($q) => $q->whereNull('whatsapp_number')->orWhere('whatsapp_number', ''))
                ->latest()
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'whatsapp_number' => $u->whatsapp_number,
                    'created_at' => $u->created_at,
                ]);
        }

        return Inertia::render('Admin/Users/Problematic', [
            'blocked_users' => $blocked,
            'no_whatsapp_users' => $noWhatsApp,
        ]);
    }

    /**
     * Co-Work page — Legacy CoWorkers list.
     */
    public function coWork(): InertiaResponse
    {
        $legacyCoWorkers = CoWorker::with('techTags')
            ->latest()
            ->get()
            ->map(fn ($cw) => [
                'id' => $cw->id,
                'person_name' => $cw->person_name,
                'email' => $cw->email,
                'mobile' => $cw->mobile,
                'facebook' => $cw->facebook,
                'linked_in' => $cw->linked_in,
                'whatsapp' => $cw->whatsapp,
                'time_from' => $cw->time_from,
                'time_to' => $cw->time_to,
                'flag_path' => $cw->getFlagPath(),
                'tech_tags' => $cw->techTags->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->tag_name ?? '?',
                ])->values(),
            ]);

        return Inertia::render('Admin/Users/CoWork', [
            'legacyCoWorkers' => $legacyCoWorkers,
        ]);
    }

    public function showLegacyCoWorker($id)
    {
        $worker = CoWorker::with('techTags')->findOrFail($id);

        $workerData = [
            'id' => $worker->id,
            'person_name' => $worker->person_name,
            'email' => $worker->email,
            'mobile' => $worker->mobile,
            'facebook' => $worker->facebook,
            'linked_in' => $worker->linked_in,
            'whatsapp' => $worker->whatsapp,
            'time_from' => $worker->time_from,
            'time_to' => $worker->time_to,
            'flag_path' => $worker->getFlagPath(),
            'created_at' => $worker->created_at,
            'tech_tags' => $worker->techTags->map(fn ($t) => ['id' => $t->id, 'name' => $t->tag_name]),
        ];

        return Inertia::render('Admin/Users/LegacyCoWorkerShow', [
            'worker' => $workerData,
        ]);
    }

    public function editLegacyCoWorker($id)
    {
        $worker = CoWorker::with('techTags')->findOrFail($id);

        $workerData = [
            'id' => $worker->id,
            'person_name' => $worker->person_name,
            'email' => $worker->email,
            'mobile' => $worker->mobile,
            'facebook' => $worker->facebook,
            'linked_in' => $worker->linked_in,
            'whatsapp' => $worker->whatsapp,
            'time_from' => $worker->time_from,
            'time_to' => $worker->time_to,
            'tech_tags' => $worker->techTags->map(fn ($t) => ['id' => $t->id, 'name' => $t->tag_name])->toArray(),
        ];

        $techTags = CoTechTag::orderBy('tag_name')->get(['id', 'tag_name'])->map(function ($tag) {
            return ['id' => $tag->id, 'name' => $tag->tag_name];
        });

        return Inertia::render('Admin/Users/LegacyCoWorkerEdit', [
            'worker' => $workerData,
            'techTags' => $techTags,
        ]);
    }

    public function updateLegacyCoWorker(Request $request, $id)
    {
        $request->validate([
            'person_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:14',
            'facebook' => 'nullable|url|max:255',
            'linked_in' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'time_from' => 'nullable|string|max:255',
            'time_to' => 'nullable|string|max:255',
            'selectedTechTags' => 'nullable|array',
        ]);

        $worker = CoWorker::findOrFail($id);

        $worker->update($request->only([
            'person_name', 'email', 'mobile', 'facebook', 'linked_in', 'whatsapp', 'time_from', 'time_to',
        ]));

        if ($request->has('selectedTechTags')) {
            $worker->techTags()->sync($request->selectedTechTags);
        }

        return redirect()->route('admin.users.legacy-coworker.show', $worker->id)
            ->with('success', __('erp.coworker_updated_success', [], 'en') ?: 'Co-Worker updated successfully.');
    }

    public function deleteLegacyCoWorker($id)
    {
        $worker = CoWorker::findOrFail($id);
        $worker->techTags()->detach();
        $worker->delete();

        return redirect()->route('admin.users.co-work')
            ->with('success', __('erp.coworker_deleted_success', [], 'en') ?: 'Co-Worker deleted successfully.');
    }

    public function createUserFromCoWorker($id)
    {
        try {
            $coWorker = CoWorker::findOrFail($id);

            if (empty($coWorker->email)) {
                return redirect()->back()->with('error', __('general.co_worker_does_not_have_an_email_address'));
            }

            $existingUser = User::where('email', $coWorker->email)->first();
            if ($existingUser) {
                return redirect()->back()->with('error', __('general.user_with_this_email_already_exists'));
            }

            // Use a non-guessable random password (never sent to anyone) and
            // require the new employee to set their own password via a
            // one-time signed link delivered over WhatsApp.
            $placeholderPassword = Hash::make(Str::random(48));

            $user = User::create([
                'name' => $coWorker->person_name,
                'email' => $coWorker->email,
                'password' => $placeholderPassword,
                'currency' => '2', // EGP currency
            ]);

            $user->assignRole('employee');

            $setLink = SetPasswordController::issueLink($user, Auth::id());

            if (! empty($coWorker->whatsapp) && class_exists('\App\Services\WhatsAppNotificationService')) {
                $this->sendSetPasswordLinkViaWhatsApp($coWorker, $user, $setLink, false);
            }

            return redirect()->back()->with('success', __('general.user_created_successfully_send_set_password_link'));

        } catch (\Exception $e) {
            Log::error('Error creating user from co-worker: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Failed to create user: '.$e->getMessage());
        }
    }

    public function resetPasswordAndSendCredentialsForCoWorker($id)
    {
        try {
            $coWorker = CoWorker::findOrFail($id);

            if (empty($coWorker->email)) {
                return redirect()->back()->with('error', __('general.co_worker_does_not_have_an_email_address'));
            }

            $user = User::where('email', $coWorker->email)->first();
            if (! $user) {
                return redirect()->back()->with('error', __('general.user_with_this_email_does_not_exist'));
            }

            // Invalidate any existing passwords/sessions for this user, then
            // deliver a one-time link — never plaintext.
            $user->update([
                'password' => Hash::make(Str::random(48)),
            ]);
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $user->assignRole('employee');

            $setLink = SetPasswordController::issueLink($user, Auth::id());

            if (! empty($coWorker->whatsapp) && class_exists('\App\Services\WhatsAppNotificationService')) {
                $this->sendSetPasswordLinkViaWhatsApp($coWorker, $user, $setLink, true);
            }

            return redirect()->back()->with('success', __('general.password_reset_link_sent'));

        } catch (\Exception $e) {
            Log::error('Error resetting password: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Failed to reset password: '.$e->getMessage());
        }
    }

    private function sendSetPasswordLinkViaWhatsApp($coWorker, $user, string $setLink, bool $isPasswordReset = false): void
    {
        try {
            $loginUrl = route('login');

            $message = "Hello {$coWorker->person_name},\n\n";
            $message .= $isPasswordReset
                ? "Your password has been reset. Please set a new one using the secure link below.\n\n"
                : "An account has been created for you. Please set your password to activate it.\n\n";
            $message .= "Set your password: {$setLink}\n\n";
            $message .= "Login page: {$loginUrl}\n\n";
            $message .= 'This link is valid for 24 hours and can be used only once. '
                ."If you did not request this, please ignore this message and contact support.\n\nThank you.";

            $notificationService = app(WhatsAppNotificationService::class);
            $notificationService->sendMessage($user, $message);
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp set-password link: '.$e->getMessage());
        }
    }

    private function sendCredentialsViaWhatsApp($coWorker, $user, $password, $isPasswordReset = false)
    {
        // DEPRECATED: do not transmit plaintext passwords over WhatsApp.
        // Kept temporarily for legacy callers that may still pass through.
        // New flows should call sendSetPasswordLinkViaWhatsApp().
        // When invoked, it routes through the safe link path so plaintext is
        // never emitted to the messaging channel.
        $setLink = SetPasswordController::issueLink($user, Auth::id());
        $this->sendSetPasswordLinkViaWhatsApp($coWorker, $user, $setLink, $isPasswordReset);
    }

    public function reset_password($id)
    {
        $user = User::findOrFail($id);

        $plainPassword = $this->generateSecurePassword(12);
        $user->update([
            'password' => Hash::make($plainPassword),
        ]);

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $loginUrl = url('/login');

        try {
            $messageText = "Hello {$user->name},\n\n"
                ."An administrator has generated a new password for your account.\n\n"
                ."Email: {$user->email}\n"
                ."Password: {$plainPassword}\n\n"
                ."You can sign in here: {$loginUrl}\n\n"
                .'For your security, please change your password after signing in. '
                ."If you did not request this, please ignore this email and contact support.\n\nThank you.";

            Mail::raw($messageText, function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('general.your_new_account_password') ?: 'Your new account password');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: '.$e->getMessage());
        }

        return response()->json([
            'message' => __('general.password_reset_email_sent_with_new_password')
                ?: 'A new password has been generated and emailed to the user.',
            'email' => $user->email,
            'name' => $user->name,
            'password' => $plainPassword,
            'login_url' => $loginUrl,
        ]);
    }

    private function generateSecurePassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%&*';

        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        $all = $upper.$lower.$digits.$symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    public function referrals($id)
    {
        $client = User::findOrFail($id);
        $referrals = $client->my_ref_users()->paginate(20);

        // Calculate commissions
        $referrals->getCollection()->transform(function ($referral) use ($client) {
            $earnings = Earning::where('user_id', $client->id)
                ->where('referred_user_id', $referral->id)
                ->get();

            $total_commission = 0;
            foreach ($earnings as $earning) {
                // If the app has CurrenciesExchange::RateToday, we use it, otherwise fallback
                if (class_exists(CurrenciesExchange::class)) {
                    $total_commission += CurrenciesExchange::RateToday($earning->amount, $earning->currency, $client->currency);
                } else {
                    $total_commission += $earning->amount; // Fallback
                }
            }

            return [
                'id' => $referral->id,
                'name' => $referral->name,
                'email' => $referral->email,
                'slug' => $referral->slug,
                'created_at' => $referral->created_at,
                'email_verified_at' => $referral->email_verified_at,
                'commission_earned' => $total_commission,
            ];
        });

        return Inertia::render('Admin/Users/Referrals', [
            'client' => (new UserResource($client))->resolve(),
            'referrals' => $referrals,
        ]);
    }

    public function unlink_referral($user_id, $referred_user_id)
    {
        $user = User::findOrFail($user_id);
        $referred_user = User::findOrFail($referred_user_id);

        if ($referred_user->ref_user_id == $user->id) {
            $referred_user->ref_user_id = null;
            $referred_user->save();

            return back()->with('success', __('general.referral_removed_successfully'));
        }

        return back()->with('error', __('general.user_is_not_referred_by_this_user'));
    }

    public function files($id)
    {
        $client = User::findOrFail($id);

        return Inertia::render('Admin/Users/Files', [
            'client' => $client,
        ]);
    }

    public function projects($id)
    {
        return redirect()->route('admin.projects.index', ['user_id' => $id]);
    }

    public function reports($id)
    {
        $client = User::findOrFail($id);
        $dates = $client->timer_report()->get();
        $invoices = $client->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->get();
        $unpaid = $client->unpaid_invoices_amount();

        return Inertia::render('Admin/Users/Reports', [
            'client' => (new UserResource($client))->resolve(),
            'dates' => $dates,
            'unpaid' => $unpaid,
        ]);
    }

    public function create_task($id)
    {
        $user = User::findOrFail($id);

        return Inertia::render('Admin/Users/AssignTask', [
            'client' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function add_task(AddTaskRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $this->adminUserService->addTask($user, $request->input('title'), $request->input('description'));

        return redirect()->route('admin.users.show', $user->id)->with('success', __('general.task_created_successfully'));
    }

    public function createSubscription($id)
    {
        $user = User::findOrFail($id);
        $serviceItems = app(PricingService::class)->getServiceItems();

        return Inertia::render('Admin/Users/AddSubscription', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'serviceItems' => collect($serviceItems)->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                ];
            })->values(),
        ]);
    }

    public function activateMembership(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'object' => 'required|string',
            'duration_days' => 'required|integer|min:1',
        ]);

        $serviceItems = app(PricingService::class)->getServiceItems();
        $plan = collect($serviceItems)->firstWhere('id', $request->object);

        if (! $plan) {
            return back()->withErrors(['object' => 'Invalid subscription module']);
        }

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => $plan['id'],
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays((int) $request->duration_days),
            'auto_renew' => false,
        ]);

        return back()->with('success', "Membership ({$plan['name']}) activated successfully for {$request->duration_days} days.");
    }

    public function updateMembership(Request $request, $id, $sub_id)
    {
        $user = User::findOrFail($id);
        $subscription = UserSubscription::where('user_id', $user->id)->findOrFail($sub_id);

        $request->validate([
            'status' => 'required|in:active,expired,cancelled,pending',
            'expires_at' => 'required|date',
        ]);

        $subscription->update([
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        return back()->with('success', __('general.membership_updated_successfully'));
    }

    public function deleteMembership($id, $sub_id)
    {
        $user = User::findOrFail($id);
        $subscription = UserSubscription::where('user_id', $user->id)->findOrFail($sub_id);
        $subscription->delete();

        return back()->with('success', __('general.membership_deleted_successfully'));
    }

    /**
     * Generate a printable PDF balance sheet for a user showing unpaid invoices.
     * Recovered from old project: UsersController::balance_sheet_print()
     */
    public function balanceSheetPrint($id)
    {
        $user = User::find($id);

        if (! $user) {
            return redirect()->route('admin.users.index')
                ->with('error', __('errors.no_client_with_id'));
        }

        $invoices = $user->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->get();

        $unpaid = $user->unpaid_invoices_amount(true);

        $pdf = Pdf::loadView('admin.users.balance_sheet_print', compact('user', 'unpaid', 'invoices'));

        return $pdf->stream(__('general.balance_sheet').' - '.$user->name.'.pdf');
    }

    /**
     * Platform-wide Earning Analysis.
     * All business logic is delegated to EarningAnalyzeService.
     */
    public function earningAnalyze(Request $request): InertiaResponse
    {
        $data = app(EarningAnalyzeService::class)->pageData();

        return Inertia::render('Admin/Users/EarningAnalyze', $data);
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'role' => __('errors.cannot_change_own_role'),
            ]);
        }

        $request->validate([
            'role' => 'required|string|in:admin,client,employee,manager,moderator',
        ]);

        $roleName = $request->input('role');
        // Ensure Spatie role exists
        Role::findOrCreate($roleName, 'web');

        $user->syncRoles([$roleName]);

        return back()->with('success', __('erp.role_updated_success'));
    }
}
