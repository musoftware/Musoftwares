<?php

namespace Modules\WebTools\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Inertia\Inertia;


class CoordinatesConverterController extends Controller
{
    /**
     * Show the decimal to degrees coordinates converter.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Utilities/CoordinatesConverter');
    }
}
