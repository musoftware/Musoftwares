<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use App\Models\CoWorker;
use App\Models\CoTechTag;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Requests\Admin\User\ToggleBlockUserRequest;
use App\Http\Requests\Admin\User\AddTaskRequest;
use App\Http\Resources\UserResource;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

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
                
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'whatsapp_number')) {
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
        $sortable  = ['name', 'email', 'created_at', 'id', 'last_activity_at'];
        $sort      = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'id';
        // The old system defaulted to ASC sorting for users
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);

        $users = $query->paginate(25)->withQueryString()->through(fn ($user) => (new UserResource($user))->resolve());

        $stats = [
            'total'           => User::count(),
            'active'          => User::where('account_status', 'active')->orWhereNull('account_status')->count(),
            'blocked'         => User::where('account_status', 'blocked')->count(),
            'kyc_verified'    => User::where('kyc_verified', true)->count(),
            'new_this_week'   => User::where('created_at', '>=', now()->subDays(7))->count(),
            'new_this_month'  => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'clients' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'kyc', 'sort', 'direction']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Show full user profile with stats.
     * Recovered from old UsersController::show()
     */
    public function show(Request $request, $id)
    {
        $user = User::with(['kycDocuments', 'kycVerifier:id,name', 'tickets', 'roles'])
            ->findOrFail($id);

        $initials = collect(explode(' ', $user->name))
            ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'))
            ->take(2)
            ->implode('');

        $walletData = [
            'balance'  => (float) $user->available_balance(),
            'currency' => $user->currency_name(),
        ];

        $stats = [
            'tickets_total'  => $user->tickets()->count(),
            'tickets_open'   => $user->tickets()->where('ticket_status', 'open')->count(),
            'kyc_docs_count' => $user->kycDocuments()->count(),
        ];

        // Fetch ERP stats using User model relations directly (IsPlatformClient trait)
        try {
            $stats['invoices_total'] = $user->invoices()->count();
            $stats['invoices_paid']  = $user->invoices()->where('status', 'paid')->count();
            $stats['invoices_unpaid_sum'] = $user->invoices()->where('status', 'unpaid')->sum('amount');
        } catch (\Throwable $e) {}

        // Try Marketplace stats
        try {
            if (class_exists('\Modules\Marketplace\Models\Order')) {
                $stats['orders_total'] = \Modules\Marketplace\Models\Order::where('buyer_id', $user->id)->count();
            }
            if (class_exists('\Modules\Marketplace\Models\Service')) {
                $stats['services_total'] = \Modules\Marketplace\Models\Service::where('seller_id', $user->id)->count();
                $stats['services_approved'] = \Modules\Marketplace\Models\Service::where('seller_id', $user->id)->where('status', 'approved')->count();
            }
        } catch (\Throwable $e) {}

        $userDetail = (new UserResource($user))->resolve();

        $transactions = $user->transactions()->latest()->take(10)->get()->map(function($tx) {
            return [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'description' => $tx->reason ?? $tx->type,
                'created_at' => $tx->created_at,
            ];
        });

        $modulePlans = \App\Models\ModulePlan::where('is_active', true)->get();
        $subscriptions = $user->subscriptions()->orderBy('expires_at', 'desc')->get();

        return Inertia::render('Admin/Users/Show', [
            'client' => $userDetail,
            'stats'  => $stats,
            'modulePlans' => $modulePlans,
            'subscriptions' => $subscriptions,
            'wallets' => [
                [
                    'id' => 'main',
                    'context' => 'Main Wallet',
                    'balance' => $walletData['balance'],
                    'currency' => $walletData['currency'],
                    'transactions' => $transactions,
                ]
            ],
        ]);
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles'      => ['admin', 'client'],
            'currencies' => \App\Models\Currency::all(),
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
            ->with('success', 'User created successfully.');
    }

    /**
     * Show edit form for an existing user.
     */
    public function edit($id)
    {
        $user = User::with(['kycDocuments', 'roles'])->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'role'                 => $user->roles->first()?->name ?? 'user',
                'phone'                => $user->phone,
                'mobile_1'             => $user->mobile_1,
                'mobile_2'             => $user->mobile_2,
                'whatsapp_number'      => $user->whatsapp_number,
                'telegram_username'    => $user->telegram_username,
                'country'              => $user->country,
                'city'                 => $user->city,
                'currency'             => $user->currency_id,
                'account_status'       => $user->account_status ?? 'active',
                'block_reason'         => $user->block_reason,
                'kyc_verified'         => (bool) $user->kyc_verified,
                'kyc_notes'            => $user->kyc_notes,
                'max_devices'          => $user->max_devices,
            ],
            'roles'      => ['admin', 'client'],
            'currencies' => \App\Models\Currency::all(),
            'plans'      => \App\Models\ModulePlan::where('is_active', true)->get(),
            'statuses'   => ['active', 'blocked', 'suspended'],
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
            ->with('success', 'User updated successfully.');
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
            ->with('success', 'User deleted.');
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
        $user  = User::findOrFail($id);
        $token = $user->createToken('admin-impersonation-' . Auth::id())->plainTextToken;

        // Store the impersonation token in session so the impersonated session can be detected
        session([
            'impersonator_id' => Auth::id(),
            'impersonate' => $user->id,
            'impersonating_user_id' => $user->id,
            'impersonated_by' => Auth::id()
        ]);

        Auth::loginUsingId($user->id);

        if ($request->wantsJson() || $request->isMethod('post')) {
            return response()->json([
                'token' => $token,
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
            ->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'account_status' => $u->account_status,
                'block_reason'   => $u->block_reason,
                'created_at'     => $u->created_at,
            ]);

        $noWhatsApp = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'whatsapp_number')) {
            $noWhatsApp = User::role('client')
                ->where(fn($q) => $q->whereNull('whatsapp_number')->orWhere('whatsapp_number', ''))
                ->latest()
                ->get()
                ->map(fn($u) => [
                    'id'             => $u->id,
                    'name'           => $u->name,
                    'email'          => $u->email,
                    'whatsapp_number'=> $u->whatsapp_number,
                    'created_at'     => $u->created_at,
                ]);
        }

        return Inertia::render('Admin/Users/Problematic', [
            'blocked_users'       => $blocked,
            'no_whatsapp_users'   => $noWhatsApp,
        ]);
    }
    /**
     * Co-Work page — two tabs:
     *  1. Freelancer System: users with skills from the Freelance module
     *  2. Legacy CoWorkers: old co_workers table records
     */
    public function coWork(): InertiaResponse
    {
        // Tab 1: Users registered in the Freelance system (have at least one skill)
        // freelanceSkills() is a belongsToMany → Skill, so items are Skill models directly.
        $freelancers = \App\Models\User::query()
            ->whereHas('freelanceSkills')
            ->with(['roles', 'freelanceSkills'])
            ->latest()
            ->get()
            ->map(fn($user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'created_at' => $user->created_at,
                'skills'     => $user->freelanceSkills->map(fn($skill) => [
                    'id'   => $skill->id,
                    'name' => $skill->name ?? '—',
                ])->values(),
            ]);

        // Tab 2: Legacy CoWorkers
        $legacyCoWorkers = \App\Models\CoWorker::with('techTags')
            ->latest()
            ->get()
            ->map(fn($cw) => [
                'id'          => $cw->id,
                'person_name' => $cw->person_name,
                'email'       => $cw->email,
                'mobile'      => $cw->mobile,
                'facebook'    => $cw->facebook,
                'linked_in'   => $cw->linked_in,
                'whatsapp'    => $cw->whatsapp,
                'time_from'   => $cw->time_from,
                'time_to'     => $cw->time_to,
                'flag_path'   => $cw->getFlagPath(),
                'tech_tags'   => $cw->techTags->map(fn($t) => [
                    'id'   => $t->id,
                    'name' => $t->tag_name ?? '?',
                ])->values(),
            ]);

        return Inertia::render('Admin/Users/CoWork', [
            'freelancers'     => $freelancers,
            'legacyCoWorkers' => $legacyCoWorkers,
        ]);
    }

    public function showLegacyCoWorker($id)
    {
        $worker = \App\Models\CoWorker::with('techTags')->findOrFail($id);
        
        $workerData = [
            'id'          => $worker->id,
            'person_name' => $worker->person_name,
            'email'       => $worker->email,
            'mobile'      => $worker->mobile,
            'facebook'    => $worker->facebook,
            'linked_in'   => $worker->linked_in,
            'whatsapp'    => $worker->whatsapp,
            'time_from'   => $worker->time_from,
            'time_to'     => $worker->time_to,
            'flag_path'   => $worker->getFlagPath(),
            'created_at'  => $worker->created_at,
            'tech_tags'   => $worker->techTags->map(fn($t) => ['id' => $t->id, 'name' => $t->tag_name]),
        ];

        return Inertia::render('Admin/Users/LegacyCoWorkerShow', [
            'worker' => $workerData
        ]);
    }

    public function editLegacyCoWorker($id)
    {
        $worker = CoWorker::with('techTags')->findOrFail($id);
        
        $workerData = [
            'id'          => $worker->id,
            'person_name' => $worker->person_name,
            'email'       => $worker->email,
            'mobile'      => $worker->mobile,
            'facebook'    => $worker->facebook,
            'linked_in'   => $worker->linked_in,
            'whatsapp'    => $worker->whatsapp,
            'time_from'   => $worker->time_from,
            'time_to'     => $worker->time_to,
            'tech_tags'   => $worker->techTags->map(fn($t) => ['id' => $t->id, 'name' => $t->tag_name])->toArray(),
        ];

        $techTags = CoTechTag::orderBy('tag_name')->get(['id', 'tag_name'])->map(function($tag) {
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
            'person_name', 'email', 'mobile', 'facebook', 'linked_in', 'whatsapp', 'time_from', 'time_to'
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
                return redirect()->back()->with('error', 'Co-worker does not have an email address.');
            }

            $existingUser = User::where('email', $coWorker->email)->first();
            if ($existingUser) {
                return redirect()->back()->with('error', 'User with this email already exists.');
            }

            $randomPassword = Str::random(12);

            $user = User::create([
                'name' => $coWorker->person_name,
                'email' => $coWorker->email,
                'password' => Hash::make($randomPassword),
                'currency' => '2', // EGP currency
            ]);

            $employeeRole = Role::createRule('Employee', 'employee');
            $user->roles()->attach($employeeRole);

            if (!empty($coWorker->whatsapp) && class_exists('\App\Services\WhatsAppNotificationService')) {
                $this->sendCredentialsViaWhatsApp($coWorker, $user, $randomPassword);
            }

            return redirect()->back()->with('success', "User created successfully as employee. Temporary Password: $randomPassword");

        } catch (\Exception $e) {
            Log::error('Error creating user from co-worker: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function resetPasswordAndSendCredentialsForCoWorker($id)
    {
        try {
            $coWorker = CoWorker::findOrFail($id);

            if (empty($coWorker->email)) {
                return redirect()->back()->with('error', 'Co-worker does not have an email address.');
            }

            $user = User::where('email', $coWorker->email)->first();
            if (!$user) {
                return redirect()->back()->with('error', 'User with this email does not exist.');
            }

            $randomPassword = Str::random(12);

            $user->update([
                'password' => Hash::make($randomPassword),
            ]);

            $employeeRole = Role::createRule('Employee', 'employee');
            if (!$user->roles()->where('slug', 'employee')->exists()) {
                $user->roles()->attach($employeeRole);
            }

            if (!empty($coWorker->whatsapp) && class_exists('\App\Services\WhatsAppNotificationService')) {
                $this->sendCredentialsViaWhatsApp($coWorker, $user, $randomPassword, true);
            }

            return redirect()->back()->with('success', "Password reset successfully. New Password: $randomPassword");

        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to reset password: ' . $e->getMessage());
        }
    }

    private function sendCredentialsViaWhatsApp($coWorker, $user, $password, $isPasswordReset = false)
    {
        try {
            $loginUrl = route('login');
            
            $message = "Hello {$coWorker->person_name},\n\n";
            $message .= $isPasswordReset ? "Your password has been reset.\n\n" : "Your account has been created as an employee.\n\n";
            $message .= "Login Credentials:\n";
            $message .= "Email: {$user->email}\n";
            $message .= "Password: {$password}\n\n";
            $message .= "Login Link: {$loginUrl}\n\n";
            $message .= "Please change your password after first login.\n\nThank you!";

            $notificationService = app(\App\Services\WhatsAppNotificationService::class);
            $notificationService->sendMessage($user, $message);
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp credentials: ' . $e->getMessage());
        }
    }

    public function reset_password($id)
    {
        $user = User::findOrFail($id);
        $newPassword = $this->adminUserService->resetPassword($user);

        return response()->json([
            'new_password' => $newPassword,
            'message' => 'Password reset successfully.'
        ]);
    }

    public function referrals($id)
    {
        $client = User::findOrFail($id);
        $referrals = $client->my_ref_users()->paginate(20);

        // Calculate commissions
        $referrals->getCollection()->transform(function ($referral) use ($client) {
            $earnings = \App\Models\Earning::where('user_id', $client->id)
                ->where('referred_user_id', $referral->id)
                ->get();
            
            $total_commission = 0;
            foreach ($earnings as $earning) {
                // If the app has CurrenciesExchange::RateToday, we use it, otherwise fallback
                if (class_exists(\App\Models\CurrenciesExchange::class)) {
                    $total_commission += \App\Models\CurrenciesExchange::RateToday($earning->amount, $earning->currency, $client->currency);
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
            'client' => (new \App\Http\Resources\UserResource($client))->resolve(),
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

            return back()->with('success', 'Referral removed successfully.');
        }

        return back()->with('error', 'User is not referred by this user.');
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
            'client' => (new \App\Http\Resources\UserResource($client))->resolve(),
            'dates' => $dates,
            'unpaid' => $unpaid,
        ]);
    }

    public function add_task(AddTaskRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $this->adminUserService->addTask($user, $request->input('title'), $request->input('description'));

        return back()->with('success', 'Task created successfully.');
    }

    public function activateMembership(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'object' => 'required|string',
            'duration_days' => 'required|integer|min:1',
        ]);

        $serviceItems = app(\App\Http\Controllers\SubscriptionController::class)->getServiceItems();
        $plan = collect($serviceItems)->firstWhere('id', $request->object);

        if (!$plan) {
            return back()->withErrors(['object' => 'Invalid subscription module']);
        }

        \App\Models\UserSubscription::create([
            'user_id' => $user->id,
            'object' => $plan['id'],
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays($request->duration_days),
            'auto_renew' => false,
        ]);

        return back()->with('success', "Membership ({$plan['name']}) activated successfully for {$request->duration_days} days.");
    }

    public function updateMembership(Request $request, $id, $sub_id)
    {
        $user = User::findOrFail($id);
        $subscription = \App\Models\UserSubscription::where('user_id', $user->id)->findOrFail($sub_id);

        $request->validate([
            'status' => 'required|in:active,expired,cancelled,pending',
            'expires_at' => 'required|date',
        ]);

        $subscription->update([
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        return back()->with('success', "Membership updated successfully.");
    }

    public function deleteMembership($id, $sub_id)
    {
        $user = User::findOrFail($id);
        $subscription = \App\Models\UserSubscription::where('user_id', $user->id)->findOrFail($sub_id);
        $subscription->delete();

        return back()->with('success', "Membership deleted successfully.");
    }

    /**
     * Generate a printable PDF balance sheet for a user showing unpaid invoices.
     * Recovered from old project: UsersController::balance_sheet_print()
     */
    public function balanceSheetPrint($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.users.index')
                ->with('error', __('errors.no_client_with_id'));
        }

        $invoices = $user->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->get();

        $unpaid = $user->unpaid_invoices_amount(true);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.users.balance_sheet_print', compact('user', 'unpaid', 'invoices'));
        return $pdf->stream(__('Balance Sheet') . ' - ' . $user->name . '.pdf');
    }

    /**
     * Platform-wide Earning Analysis.
     * All business logic is delegated to EarningAnalyzeService.
     */
    public function earningAnalyze(Request $request): InertiaResponse
    {
        $data = app(\App\Services\EarningAnalyzeService::class)->pageData();

        return Inertia::render('Admin/Users/EarningAnalyze', $data);
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => __('errors.cannot_change_own_role'),
            ]);
        }

        $request->validate([
            'role' => 'required|string|in:admin,client,user,employee,manager,moderator',
        ]);

        $roleName = $request->input('role');
        // Ensure Spatie role exists
        \Spatie\Permission\Models\Role::findOrCreate($roleName, 'web');

        $user->syncRoles([$roleName]);

        return back()->with('success', __('erp.role_updated_success'));
    }
}

