<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SoftwareStore\Models\FreeQrMenu;
use Modules\SoftwareStore\Services\FreeTools;
use Modules\SoftwareStore\Services\QrMenuKit;

/**
 * Free tools listed in /services. The tools themselves run in the browser; only the QR menu maker
 * stores anything (the menu, so the printed QR code keeps working).
 */
class FreeToolController extends Controller
{
    public function index()
    {
        return view('softwarestore::tools.index');
    }

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

    /** A guest gets an edit token to keep; a signed-in maker owns the menu and needs no token. */
    public function storeMenu(Request $request): JsonResponse
    {
        [$menu, $token] = FreeQrMenu::createWithToken($this->validatedMenu($request), $request->ip(), $request->user()?->id);

        return response()->json(['code' => $menu->code, 'token' => $menu->user_id ? null : $token, 'url' => $menu->publicUrl()], 201);
    }

    public function updateMenu(Request $request, string $code): JsonResponse
    {
        $menu = $this->editableMenu($request, $code);
        $menu->update(['data' => $this->validatedMenu($request)] + ($menu->user_id ? [] : ['user_id' => $request->user()?->id]));

        return response()->json(['code' => $menu->code, 'url' => $menu->publicUrl()]);
    }

    public function menuData(Request $request, string $code): JsonResponse
    {
        $menu = $this->editableMenu($request, $code);

        return response()->json(['data' => $menu->data, 'url' => $menu->publicUrl()]);
    }

    /** The signed-in maker's menus, for the list at the top of the editor. */
    public function myMenus(Request $request): JsonResponse
    {
        return response()->json(FreeQrMenu::where('user_id', $request->user()->id)->latest('updated_at')->get()->map(fn (FreeQrMenu $menu) => [
            'code' => $menu->code,
            'name' => $menu->data['name'] ?? $menu->code,
            'url' => $menu->publicUrl(),
            'views' => $menu->views,
            'updated_at' => $menu->updated_at?->toIso8601String(),
        ]));
    }

    /** A photo for a menu: re-encoded to WebP on our side, then referenced as "up:<id>". */
    public function uploadMenuImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png,webp|max:6144']);
        $ref = QrMenuKit::storeUpload($request->file('image'));
        abort_unless($ref, 422, __('free_tools.tools.qr-menu-maker.form.image_error'));

        return response()->json(['ref' => $ref, 'url' => QrMenuKit::url($ref)], 201);
    }

    public function menu(Request $request, string $code)
    {
        if ($code === QrMenuKit::DEMO_CODE) {
            $lang = $request->query('lang') === 'en' ? 'en' : 'ar';

            return view('softwarestore::tools.menu', ['menu' => null, 'data' => QrMenuKit::demo($lang), 'demo' => true]);
        }

        $menu = FreeQrMenu::where('code', $code)->firstOrFail();
        $menu->increment('views');

        return view('softwarestore::tools.menu', ['menu' => $menu, 'data' => $menu->data, 'demo' => false]);
    }

    private function editableMenu(Request $request, string $code): FreeQrMenu
    {
        $menu = FreeQrMenu::where('code', $code)->firstOrFail();
        $owner = $menu->user_id && $request->user()?->id === $menu->user_id;
        abort_unless($owner || $menu->tokenMatches($request->header('X-Menu-Token')), 403);

        return $menu;
    }

    /** Text, our own photo references and a few fixed choices only: the menu is shown on our domain. */
    private function validatedMenu(Request $request): array
    {
        $photo = ['nullable', 'string', 'max:40', function (string $attribute, $value, $fail) {
            if (! QrMenuKit::isValidRef($value)) {
                $fail(__('free_tools.tools.qr-menu-maker.form.image_error'));
            }
        }];

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'tagline' => 'nullable|string|max:140',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+ ]*$/'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+ ]*$/'],
            'address' => 'nullable|string|max:140',
            'hours' => 'nullable|string|max:80',
            'currency' => 'required|string|in:EGP,SAR,AED,KWD,QAR,USD',
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme' => 'nullable|in:light,dark',
            'lang' => 'required|in:ar,en',
            'cover' => $photo,
            'logo' => $photo,
            'sections' => 'required|array|min:1|max:20',
            'sections.*.title' => 'required|string|max:60',
            'sections.*.items' => 'required|array|min:1|max:40',
            'sections.*.items.*.name' => 'required|string|max:80',
            'sections.*.items.*.price' => 'nullable|numeric|min:0|max:1000000',
            'sections.*.items.*.note' => 'nullable|string|max:140',
            'sections.*.items.*.image' => $photo,
            'sections.*.items.*.badges' => 'nullable|array|max:4',
            'sections.*.items.*.badges.*' => 'in:'.implode(',', QrMenuKit::BADGES),
            'sections.*.items.*.sold_out' => 'nullable|boolean',
        ]);

        $data['theme'] = $data['theme'] ?? 'light';
        $data['sections'] = array_map(fn (array $section) => [
            'title' => $section['title'],
            'items' => array_map(fn (array $item) => [
                'name' => $item['name'],
                'price' => isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : null,
                'note' => $item['note'] ?? null,
                'image' => $item['image'] ?? null,
                'badges' => array_values(array_unique($item['badges'] ?? [])),
                'sold_out' => (bool) ($item['sold_out'] ?? false),
            ], array_values($section['items'])),
        ], array_values($data['sections']));

        return $data;
    }
}
