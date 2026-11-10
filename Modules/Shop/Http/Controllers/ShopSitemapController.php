<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Shop\Services\ShopSitemap;

class ShopSitemapController extends Controller
{
    public function show(ShopSitemap $sitemap): Response
    {
        return response()
            ->view('shop::sitemap', ['entries' => $sitemap->entries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
