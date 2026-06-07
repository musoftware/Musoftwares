<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageLine;
use App\Services\TranslationService;
use App\Http\Resources\LanguageLineResource;
use App\Http\Requests\Admin\StoreLanguageLineRequest;
use App\Http\Requests\Admin\UpdateLanguageLineRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AdminLanguageLineController extends Controller
{
    public function __construct(
        protected TranslationService $translationService
    ) {}

    public function index(Request $request)
    {
        $query = LanguageLine::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('group', 'like', "%{$search}%")
                  ->orWhere('text', 'like', "%{$search}%");
            });
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        $lines = $query->latest()
                       ->paginate(50)
                       ->withQueryString()
                       ->through(fn($l) => (new LanguageLineResource($l))->resolve());

        $groups = LanguageLine::distinct()->pluck('group');
        $supportedLocales = array_keys(config('languages.supported', ['en' => []]));

        return Inertia::render('Admin/LanguageLines/Index', [
            'lines'            => $lines,
            'groups'           => $groups,
            'supportedLocales' => $supportedLocales,
            'filters'          => $request->only(['search', 'group']),
        ]);
    }

    public function store(StoreLanguageLineRequest $request)
    {
        LanguageLine::create($request->validated());

        return redirect()->back()->with('success', __('general.translation_key_created_successfully'));
    }

    public function update(UpdateLanguageLineRequest $request, LanguageLine $languageLine)
    {
        $languageLine->update($request->validated());

        return redirect()->back()->with('success', __('general.translation_updated_successfully'));
    }

    public function destroy(LanguageLine $languageLine)
    {
        $languageLine->delete();

        return redirect()->back()->with('success', __('general.translation_key_deleted_successfully'));
    }

    public function autoTranslate(Request $request)
    {
        $request->validate([
            'text'          => 'required|string',
            'target_locale' => 'required|string',
            'source_locale' => 'nullable|string',
        ]);

        $translated = $this->translationService->translate(
            $request->text,
            $request->target_locale,
            $request->source_locale ?? 'en'
        );

        return response()->json([
            'success'    => true,
            'translated' => $translated,
        ]);
    }

    public function import(Request $request)
    {
        $locales = array_keys(config('languages.supported', ['en' => []]));
        $count = 0;
        $langPath = app()->langPath();

        foreach ($locales as $locale) {
            $jsonFile = $langPath . DIRECTORY_SEPARATOR . "{$locale}.json";
            if (file_exists($jsonFile)) {
                $translations = json_decode(file_get_contents($jsonFile), true);
                if (is_array($translations)) {
                    foreach ($translations as $key => $value) {
                        if (!is_string($value)) continue;

                        $line = LanguageLine::firstOrNew(['group' => '*', 'key' => $key]);
                        $text = $line->text ?? [];
                        
                        if (!isset($text[$locale]) || empty($text[$locale])) {
                            $text[$locale] = $value;
                            $line->text = $text;
                            $line->save();
                            $count++;
                        }
                    }
                }
            }

            $localeDir = $langPath . DIRECTORY_SEPARATOR . $locale;
            if (is_dir($localeDir)) {
                $files = glob($localeDir . DIRECTORY_SEPARATOR . '*.php');
                foreach ($files as $file) {
                    $group = basename($file, '.php');
                    try {
                        $translations = include($file);
                    } catch (\Exception $e) {
                        continue;
                    }
                    
                    if (!is_array($translations)) continue;

                    foreach (\Illuminate\Support\Arr::dot($translations) as $key => $value) {
                        if (!is_string($value)) continue;

                        $line = LanguageLine::firstOrNew(['group' => $group, 'key' => $key]);
                        $text = $line->text ?? [];
                        
                        if (!isset($text[$locale]) || empty($text[$locale])) {
                            $text[$locale] = $value;
                            $line->text = $text;
                            $line->save();
                            $count++;
                        }
                    }
                }
            }
        }

        Cache::flush();

        return back()->with('success', __('general.imported_count_translation_strings_from_files_and_json'));
    }
}
