<?php

namespace Modules\WebTools\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Inertia\Inertia;


class CipherIdentifierController extends Controller
{
    /**
     * Show the cipher identifier tool.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Utilities/CipherIdentifier');
    }
}
