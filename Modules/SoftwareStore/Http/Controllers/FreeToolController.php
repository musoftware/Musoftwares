<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SoftwareStore\Models\FreeQrMenu;
use Modules\SoftwareStore\Services\FreeTools;

/**
 * Free tools listed in /services. The tools themselves run in the browser; only the QR menu maker
 * stores anything (the menu, so the printed QR code keeps working).
 */
class FreeToolController extends Controller
{
    public function show(Request $request, string $slug)
    {
        abort_unless(FreeTools::exists($slug), 404);

        FreeTools::recordView($slug, $request->userAgent());

        return view('softwarestore::tools.show', [
            'slug' => $slug,
            'copy' => __("free_tools.tools.{$slug}"),
            'related' => FreeTools::relatedProducts($slug),
        ]);
    }

    public function storeMenu(Request $request): JsonResponse
    {
        [$menu, $token] = FreeQrMenu::createWithToken($this->validatedMenu($request), $request->ip());

        return response()->json(['code' => $menu->code, 'token' => $token, 'url' => $menu->publicUrl()], 201);
    }

    public function updateMenu(Request $request, string $code): JsonResponse
    {
        $menu = FreeQrMenu::where('code', $code)->firstOrFail();
        abort_unless($menu->tokenMatches($request->header('X-Menu-Token')), 403);

        $menu->update(['data' => $this->validatedMenu($request)]);

        return response()->json(['code' => $menu->code, 'url' => $menu->publicUrl()]);
    }

    public function menuData(Request $request, string $code): JsonResponse
    {
        $menu = FreeQrMenu::where('code', $code)->firstOrFail();
        abort_unless($menu->tokenMatches($request->header('X-Menu-Token')), 403);

        return response()->json(['data' => $menu->data, 'url' => $menu->publicUrl()]);
    }

    public function menu(string $code)
    {
        $menu = FreeQrMenu::where('code', $code)->firstOrFail();
        $menu->increment('views');

        return view('softwarestore::tools.menu', ['menu' => $menu, 'data' => $menu->data]);
    }

    /** Plain text only: the menu is shown on our domain, so no links, HTML or images are accepted. */
    private function validatedMenu(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'tagline' => 'nullable|string|max:140',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+ ]*$/'],
            'currency' => 'required|string|in:EGP,SAR,AED,KWD,QAR,USD',
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'lang' => 'required|in:ar,en',
            'sections' => 'required|array|min:1|max:20',
            'sections.*.title' => 'required|string|max:60',
            'sections.*.items' => 'required|array|min:1|max:40',
            'sections.*.items.*.name' => 'required|string|max:80',
            'sections.*.items.*.price' => 'nullable|numeric|min:0|max:1000000',
            'sections.*.items.*.note' => 'nullable|string|max:140',
        ]);

        $data['sections'] = array_map(fn (array $section) => [
            'title' => $section['title'],
            'items' => array_map(fn (array $item) => [
                'name' => $item['name'],
                'price' => isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : null,
                'note' => $item['note'] ?? null,
            ], array_values($section['items'])),
        ], array_values($data['sections']));

        return $data;
    }
}
