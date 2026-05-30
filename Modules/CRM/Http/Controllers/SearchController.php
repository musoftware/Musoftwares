<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\Lead;

    /**
     * Display the Universal Search UI page.
     */
    public function index()
    {
        return \Inertia\Inertia::render('CRM/Search/Index');
    }


}
