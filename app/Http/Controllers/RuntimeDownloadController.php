<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class RuntimeDownloadController extends Controller
{
    /**
     * Display the runtime download page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
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



