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
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }

        return Inertia::render('ERP/Backup/Index', [
            'hasBackupFeature' => $user ? $user->hasModuleSubscription('erp-backup') : false,
        ]);
    }

    public function download(BackupService $backupService)
    {
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }
        
        if (!$user || !$user->hasModuleSubscription('erp-backup')) {
            abort(403, __('errors.erp_backup_addon_required'));
        }

        $tenant = auth('erp_team')->user()->tenant;
        $filePath = $backupService->createBackup($tenant);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function restore(Request $request, BackupService $backupService)
    {
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }

        if (!$user || !$user->hasModuleSubscription('erp-backup')) {
            abort(403, __('errors.erp_backup_addon_required'));
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:zip,json',
        ]);

        $tenant = auth('erp_team')->user()->tenant;

        try {
            $backupService->restoreBackup($tenant, $request->file('backup_file'));
            return back()->with('success', __('erp.backup_restored_success'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Backup Restore Failed: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return back()->with('error', __('errors.backup_restore_failed', ['message' => $e->getMessage()]));
        }
    }
}
