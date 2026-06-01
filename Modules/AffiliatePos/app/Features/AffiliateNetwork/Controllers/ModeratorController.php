<?php

namespace Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\User;

class ModeratorController extends Controller
{
    public function index()
    {
        // Assuming a parent_id column or a specialized relationship exists on User model for SaaS
        $moderators = User::where('parent_id', Auth::id())
            ->whereHas('roles', function($q) {
                $q->where('name', 'moderator');
            })
            ->latest()
            ->paginate(20);
            
        return Inertia::render('AffiliatePos/Affiliate/Moderators/Index', [
            'moderators' => $moderators
        ]);
    }

    public function create()
    {
        return Inertia::render('AffiliatePos/Affiliate/Moderators/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $moderator = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'parent_id' => Auth::id(), // Link the moderator to the affiliate
            'tenant_id' => $request->header('X-Tenant-ID') ?? 1
        ]);

        // Assumes spatie/laravel-permission or similar is used in Musoftwares core
        $moderator->assignRole('moderator');

        return back()->with('success', __('general.moderator_created_successfully'));
    }

    public function destroy(User $moderator)
    {
        if ($moderator->parent_id !== Auth::id()) {
            abort(403, __('general.unauthorized_access'));
        }

        $moderator->delete();
        
        return back()->with('success', __('general.moderator_deleted_successfully'));
    }
}
