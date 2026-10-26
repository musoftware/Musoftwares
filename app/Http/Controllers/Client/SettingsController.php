<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\SiteNav;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One place for a client's settings, laid out like a phone settings screen: each row opens the page
 * that already handles it. The language rows are ?lang= links; SetLocale keeps the choice on the account.
 */
class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Client/Settings/Index', [
            'currentLang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'whatsappUrl' => SiteNav::WHATSAPP_URL,
        ]);
    }
}
