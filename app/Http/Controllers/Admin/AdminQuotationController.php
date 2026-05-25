<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminQuotationController extends Controller
{
    /**
     * Renders a printable view of a contract/quotation.
     */
    public function print(Request $request, Contract $contract)
    {
        $lang = $request->get('lang', $contract->lang ?? 'ar');

        $siteName = AdminSettings::GetValue('site_name', 'Musoftware');
        $contactEmail = AdminSettings::GetValue('contact_email', 'admin@musoftwares.com');
        $logoDark = config('app.logo');

        return Inertia::render('Client/Contracts/Quotation', [
            'quotation'    => $contract,
            'printLang'    => $lang,
            'siteName'     => $siteName,
            'contactEmail' => $contactEmail,
            'logoDark'     => $logoDark,
        ]);
    }
}
