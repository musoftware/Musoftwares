<?php

namespace Modules\SoftwareStore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SoftwareStore\Services\MarketingKit;
use Modules\SoftwareStore\Services\RecoveryDiscount;

class AdminMarketingController extends Controller
{
    /** One screen with ready-to-post text for every visible product. */
    public function index(MarketingKit $kit, RecoveryDiscount $discount): Response
    {
        return Inertia::render('Admin/Marketing/Index', [
            'kits' => $kit->forAll()->values(),
            'recoveryDiscount' => ['percent' => $discount->percent(), 'max' => RecoveryDiscount::MAX_PERCENT, 'hours' => RecoveryDiscount::VALID_HOURS],
        ]);
    }

    /** The discount in the unpaid-order reminder email. 0 turns it off. */
    public function updateDiscount(Request $request): RedirectResponse
    {
        $data = $request->validate(['percent' => ['required', 'integer', 'min:0', 'max:'.RecoveryDiscount::MAX_PERCENT]]);

        AdminSettings::SetValue(RecoveryDiscount::SETTING, $data['percent']);

        return back()->with('success', __('general.saved_successfully'));
    }
}
