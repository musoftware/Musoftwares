<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceUgcCreator;

class UgcController extends Controller
{
    public function index()
    {
        $creators = IntelligenceUgcCreator::orderBy('name', 'asc')->get();

        return Inertia::render('Intelligence/Ugc/Index', [
            'creators' => $creators
        ]);
    }
}
