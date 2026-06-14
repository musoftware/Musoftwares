<?php

namespace Modules\WebTools\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Inertia\Inertia;


class JsObfuscatorController extends Controller
{
    /**
     * Show the JavaScript obfuscator tool.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Utilities/JsObfuscator');
    }
}
