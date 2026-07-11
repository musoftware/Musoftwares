<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RuntimeDownloadController extends Controller
{
    /**
     * Display the runtime download page.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Runtime/Download', [
            'downloadUrl' => url('/downloads/runtime/windows/musoftware-runtime-win.zip'),
            'password' => 'musoftware2026',
            'version' => '2026.06.20.4',
        ]);
    }
}
