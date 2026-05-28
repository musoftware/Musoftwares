<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Services\BackupService;

class BackupController extends Controller
{
    public function index()
    {
        // Resolve the correct user (handles both web and erp_team guards)
        $user = auth()->user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }

        return Inertia::render('ERP/Backup/Index', [
            'hasBackupFeature' => $user ? $user->hasModuleSubscription('erp-backup') : false,
        ]);
    }

    public function download(BackupService $backupService)
    {
        $user = auth()->user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        
        if (!$user || !$user->hasModuleSubscription('erp-backup')) {
            abort(403, __('errors.erp_backup_addon_required'));
        }

        $tenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->firstOrFail();
        $filePath = $backupService->createBackup($tenant);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function restore(Request $request, BackupService $backupService)
    {
        $user = auth()->user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }

        if (!$user || !$user->hasModuleSubscription('erp-backup')) {
            abort(403, __('errors.erp_backup_addon_required'));
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:zip,json',
        ]);

        $tenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->firstOrFail();

        try {
            $backupService->restoreBackup($tenant, $request->file('backup_file'));
            return back()->with('success', __('erp.backup_restored_success'));
        } catch (\Exception $e) {
            return back()->with('error', __('errors.backup_restore_failed', ['message' => $e->getMessage()]));
        }
    }
}
