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
        return Inertia::render('ERP/Backup/Index');
    }

    public function download(BackupService $backupService)
    {
        if (!feature('erp-backup')) {
            abort(403, 'Upgrade to enable ERP Backup.');
        }

        $filePath = $backupService->createBackup(tenant());

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function restore(Request $request, BackupService $backupService)
    {
        if (!feature('erp-backup')) {
            abort(403, 'Upgrade to enable ERP Backup.');
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:zip,json',
        ]);

        try {
            $backupService->restoreBackup(tenant(), $request->file('backup_file'));
            return back()->with('success', 'Backup restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }
}
