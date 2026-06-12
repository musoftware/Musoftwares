<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\WebsiteService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebsiteServiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/WebsiteServices/Index', [
            'services' => WebsiteService::latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/WebsiteServices/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'image_en' => 'nullable|image|max:2048',
            'image_ar' => 'nullable|image|max:2048',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_description_en' => 'nullable|string|max:500',
            'seo_description_ar' => 'nullable|string|max:500',
            'seo_keywords_en' => 'nullable|string|max:500',
            'seo_keywords_ar' => 'nullable|string|max:500'
        ]);

        if ($request->hasFile('image_en')) {
            $fileEn = $request->file('image_en');
            $filenameEn = time() . '_en_' . $fileEn->getClientOriginalName();
            $fileEn->move(public_path('website_services'), $filenameEn);
            $data['image_path_en'] = 'website_services/' . $filenameEn;
        }
        
        if ($request->hasFile('image_ar')) {
            $fileAr = $request->file('image_ar');
            $filenameAr = time() . '_ar_' . $fileAr->getClientOriginalName();
            $fileAr->move(public_path('website_services'), $filenameAr);
            $data['image_path_ar'] = 'website_services/' . $filenameAr;
        }

        unset($data['image_en'], $data['image_ar']);

        $data['slug'] = $this->generateAiSlug($data['title_en'] ?: $data['title_ar']);

        // Generate SEO Data if missing
        if (empty($data['seo_title_en']) || empty($data['seo_description_en']) || empty($data['seo_keywords_en'])) {
            $seoEn = $this->generateAiSeoData($data['title_en'], $data['description_en'] ?? $data['subtitle_en'] ?? '', 'en');
            if ($seoEn) {
                $data['seo_title_en'] = $data['seo_title_en'] ?: ($seoEn['seo_title'] ?? null);
                $data['seo_description_en'] = $data['seo_description_en'] ?: ($seoEn['seo_description'] ?? null);
                $data['seo_keywords_en'] = $data['seo_keywords_en'] ?: ($seoEn['seo_keywords'] ?? null);
            }
        }

        if (empty($data['seo_title_ar']) || empty($data['seo_description_ar']) || empty($data['seo_keywords_ar'])) {
            $seoAr = $this->generateAiSeoData($data['title_ar'], $data['description_ar'] ?? $data['subtitle_ar'] ?? '', 'ar');
            if ($seoAr) {
                $data['seo_title_ar'] = $data['seo_title_ar'] ?: ($seoAr['seo_title'] ?? null);
                $data['seo_description_ar'] = $data['seo_description_ar'] ?: ($seoAr['seo_description'] ?? null);
                $data['seo_keywords_ar'] = $data['seo_keywords_ar'] ?: ($seoAr['seo_keywords'] ?? null);
            }
        }

        WebsiteService::create($data);
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_created'));
    }

    public function edit(WebsiteService $website_service)
    {
        return Inertia::render('Admin/WebsiteServices/Edit', [
            'service' => $website_service
        ]);
    }

    public function update(Request $request, WebsiteService $website_service)
    {
        $data = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'image_en' => 'nullable|image|max:2048',
            'image_ar' => 'nullable|image|max:2048',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_description_en' => 'nullable|string|max:500',
            'seo_description_ar' => 'nullable|string|max:500',
            'seo_keywords_en' => 'nullable|string|max:500',
            'seo_keywords_ar' => 'nullable|string|max:500'
        ]);

        if ($request->hasFile('image_en')) {
            if ($website_service->image_path_en && File::exists(public_path($website_service->image_path_en))) {
                File::delete(public_path($website_service->image_path_en));
            }
            $fileEn = $request->file('image_en');
            $filenameEn = time() . '_en_' . $fileEn->getClientOriginalName();
            $fileEn->move(public_path('website_services'), $filenameEn);
            $data['image_path_en'] = 'website_services/' . $filenameEn;
        }

        if ($request->hasFile('image_ar')) {
            if ($website_service->image_path_ar && File::exists(public_path($website_service->image_path_ar))) {
                File::delete(public_path($website_service->image_path_ar));
            }
            $fileAr = $request->file('image_ar');
            $filenameAr = time() . '_ar_' . $fileAr->getClientOriginalName();
            $fileAr->move(public_path('website_services'), $filenameAr);
            $data['image_path_ar'] = 'website_services/' . $filenameAr;
        }
        
        unset($data['image_en'], $data['image_ar']);

        // Update slug if title changed
        if ($website_service->title_en !== $data['title_en'] || $website_service->title_ar !== $data['title_ar']) {
            $data['slug'] = $this->generateAiSlug($data['title_en'] ?: $data['title_ar']);
        }

        // Generate SEO Data if missing
        if (empty($data['seo_title_en']) || empty($data['seo_description_en']) || empty($data['seo_keywords_en'])) {
            $seoEn = $this->generateAiSeoData($data['title_en'], $data['description_en'] ?? $data['subtitle_en'] ?? '', 'en');
            if ($seoEn) {
                $data['seo_title_en'] = $data['seo_title_en'] ?: ($seoEn['seo_title'] ?? null);
                $data['seo_description_en'] = $data['seo_description_en'] ?: ($seoEn['seo_description'] ?? null);
                $data['seo_keywords_en'] = $data['seo_keywords_en'] ?: ($seoEn['seo_keywords'] ?? null);
            }
        }

        if (empty($data['seo_title_ar']) || empty($data['seo_description_ar']) || empty($data['seo_keywords_ar'])) {
            $seoAr = $this->generateAiSeoData($data['title_ar'], $data['description_ar'] ?? $data['subtitle_ar'] ?? '', 'ar');
            if ($seoAr) {
                $data['seo_title_ar'] = $data['seo_title_ar'] ?: ($seoAr['seo_title'] ?? null);
                $data['seo_description_ar'] = $data['seo_description_ar'] ?: ($seoAr['seo_description'] ?? null);
                $data['seo_keywords_ar'] = $data['seo_keywords_ar'] ?: ($seoAr['seo_keywords'] ?? null);
            }
        }

        $website_service->update($data);
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_updated'));
    }

    public function destroy(WebsiteService $website_service)
    {
        if ($website_service->image_path_en && File::exists(public_path($website_service->image_path_en))) {
            File::delete(public_path($website_service->image_path_en));
        }
        if ($website_service->image_path_ar && File::exists(public_path($website_service->image_path_ar))) {
            File::delete(public_path($website_service->image_path_ar));
        }
        $website_service->delete();
        return redirect()->route('admin.website-services.index')->with('success', __('admin.service_deleted'));
    }

    private function generateAiSlug($title)
    {
        try {
            $apiKey = config('services.openai.key');
            if (!$apiKey) {
                return Str::slug($title);
            }

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a URL slug generator. Return only the URL friendly slug in english (lowercase, hyphens instead of spaces) for the provided text. No explanation, no quotes.'],
                        ['role' => 'user', 'content' => $title]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 50,
                ]);

            if ($response->successful()) {
                $slug = trim($response->json()['choices'][0]['message']['content'] ?? '');
                if (!empty($slug)) {
                    // Ensure unique slug
                    $originalSlug = Str::slug($slug);
                    $finalSlug = $originalSlug;
                    $counter = 1;
                    while (WebsiteService::where('slug', $finalSlug)->exists()) {
                        $finalSlug = $originalSlug . '-' . $counter;
                        $counter++;
                    }
                    return $finalSlug;
                }
            }
        } catch (\Exception $e) {
            \Log::error('AI Slug Generation failed: ' . $e->getMessage());
        }

        // Fallback to basic Str::slug
        $fallback = Str::slug($title);
        $finalSlug = $fallback;
        $counter = 1;
        while (WebsiteService::where('slug', $finalSlug)->exists()) {
            $finalSlug = $fallback . '-' . $counter;
            $counter++;
        }
        return $finalSlug;
    }

    private function generateAiSeoData($title, $description, $language)
    {
        try {
            $apiKey = config('services.openai.key');
            if (!$apiKey) {
                return null;
            }

            $langPrompt = $language === 'ar' ? 'in Arabic' : 'in English';
            
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => "You are an SEO expert. Generate a JSON object containing 'seo_title' (max 60 chars), 'seo_description' (max 160 chars), and 'seo_keywords' (comma separated) for the provided service $langPrompt. Return strictly JSON."],
                        ['role' => 'user', 'content' => "Title: $title\nDescription: $description"]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return json_decode($response->json()['choices'][0]['message']['content'] ?? '{}', true);
            }
        } catch (\Exception $e) {
            \Log::error('AI SEO Generation failed: ' . $e->getMessage());
        }

        return null;
    }
}
