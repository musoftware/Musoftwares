<?php

namespace Modules\fbmb\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\fbmb\Services\FbmbLookupService;
use Exception;

class FbmbLookupController extends Controller
{
    protected FbmbLookupService $lookupService;

    public function __construct(FbmbLookupService $lookupService)
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
