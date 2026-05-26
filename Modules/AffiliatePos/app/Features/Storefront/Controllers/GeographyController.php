<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Controllers;

use App\Http\Controllers\Controller;
// Assuming Governorate and City models exist in core app
use App\Models\Governorate;
use App\Models\City;

class GeographyController extends Controller
{
    public function governorates()
    {
        // For fallback if the core doesn't have it, we just return empty array
        if (class_exists(Governorate::class)) {
            return response()->json(Governorate::all());
        }
        return response()->json([]);
    }

    public function cities($governorateId)
    {
        if (class_exists(City::class)) {
            return response()->json(City::where('governorate_id', $governorateId)->get());
        }
        return response()->json([]);
    }
}
