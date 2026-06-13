<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\TenantDataService;
use Illuminate\Support\Facades\Response;

class TenantBackupController extends Controller
{
    protected $dataService;

    public function __construct(TenantDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function index()
    {
        return Inertia::render('Client/Settings/Backup');
    }

    public function export(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->dataService->exportData($userId);

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'musoftware_backup_' . date('Y-m-d_H-i-s') . '.json';

        return Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json|max:10240', // max 10MB
        ]);

        try {
            $fileContent = file_get_contents($request->file('backup_file')->getRealPath());
            $data = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->with('error', __('general.invalid_json_file'));
            }

            $this->dataService->importData($request->user()->id, $data);

            return redirect()->back()->with('success', __('general.data_successfully_restored_your_crm_and_erp_records_have_been_updated'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
}
