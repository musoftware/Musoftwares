<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Modules\ERP\Models\SmtpSetting;

class SmtpSettingController extends Controller
{
    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    public function edit()
    {
        $tenant = $this->resolveTenant();
        $user = Auth::user();
        
        if (!$user->hasModuleSubscription('erp-smtp')) {
            abort(403, __('errors.erp_smtp_addon_required'));
        }

        $smtp = $tenant->smtpSetting;

        return Inertia::render('ERP/Settings/Smtp', [
            'smtp' => $smtp ? [
                'host' => $smtp->host,
                'port' => $smtp->port,
                'username' => $smtp->username,
                'password' => $smtp->password ? '********' : '',
                'encryption' => $smtp->encryption,
                'from_address' => $smtp->from_address,
                'from_name' => $smtp->from_name,
            ] : null
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $this->resolveTenant();
        $user = Auth::user();
        
        if (!$user->hasModuleSubscription('erp-smtp')) {
            abort(403, __('errors.erp_smtp_addon_required'));
        }

        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|string|in:tls,ssl',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
        ]);

        $smtp = $tenant->smtpSetting()->firstOrNew();
        
        $smtp->host = $validated['host'];
        $smtp->port = $validated['port'];
        $smtp->username = $validated['username'];
        $smtp->encryption = $validated['encryption'];
        $smtp->from_address = $validated['from_address'];
        $smtp->from_name = $validated['from_name'];

        if (!empty($validated['password']) && $validated['password'] !== '********') {
            $smtp->password = $validated['password'];
        }

        $smtp->save();

        return back()->with('success', __('erp.smtp_settings_updated'));
    }
}
