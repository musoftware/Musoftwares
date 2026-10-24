<?php

namespace App\Http\Controllers;

use App\Services\IndexNowService;

/** Serves /{key}.txt, the file IndexNow engines read to confirm we own the site. */
class IndexNowKeyController extends Controller
{
    public function __invoke(string $key, IndexNowService $indexNow)
    {
        abort_unless(hash_equals($indexNow->key(), $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
