<?php

namespace Modules\Intelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Intelligence\Services\ISaasLookupService;
use Modules\Core\Services\WalletService;
use Illuminate\Support\Facades\Storage;
use Exception;

class ISaasController extends Controller
{
    protected ISaasLookupService $lookupService;

    public function __construct(ISaasLookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function index()
    {
        return \Inertia\Inertia::render('Intelligence/ISaas/Index');
    }

    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,csv',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp_isaas');
        $fullPath = storage_path('app/' . $path);

        try {
            $result = $this->lookupService->processFile(auth()->user(), $fullPath);
            
            return response()->download($result['result_path'])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
