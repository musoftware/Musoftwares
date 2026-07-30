<?php

namespace Modules\Listing\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaseetScraperService
{
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Fetch job listings from Waseet Egypt and extract details.
     *
     * @param string $listUrl
     * @param int $limit
     * @return array
     */
    public function scrapeJobs(string $listUrl, int $limit = 50): array
    {
        Log::info("[WaseetScraper] Starting scrape for URL: {$listUrl} (Limit: {$limit})");

        $html = $this->fetchUrl($listUrl);
        if (!$html) {
            Log::error("[WaseetScraper] Failed to fetch list page: {$listUrl}");
            return [];
        }

        // Find all links to posts (e.g. /ar/post/12345-title)
        preg_match_all('/href="(\/ar\/post\/\d+-[^"]+)"/', $html, $matches);
        $postPaths = array_unique($matches[1] ?? []);

        Log::info("[WaseetScraper] Found " . count($postPaths) . " listing links on page.");

        $listings = [];
        $count = 0;

        foreach ($postPaths as $path) {
            if ($count >= $limit) {
                break;
            }

            $detailUrl = 'https://eg.waseet.net' . $path;
            Log::info("[WaseetScraper] Fetching listing details from: {$detailUrl}");

            $detailHtml = $this->fetchUrl($detailUrl);
            if (!$detailHtml) {
                Log::warning("[WaseetScraper] Failed to fetch detail page: {$detailUrl}");
                continue;
            }

            $adData = $this->parseListingDetail($detailHtml);
            if (!$adData) {
                Log::debug("[WaseetScraper] Could not parse structured JSON from: {$detailUrl}");
                continue;
            }

            // Enforce requirement: MUST have both email and phone number
            $email = $adData['email'] ?? null;
            $phone = $adData['phone'] ?? null;

            if (empty($email) || empty($phone)) {
                Log::debug("[WaseetScraper] Skipping listing (missing email or phone): ID {$adData['waseet_id']} | Email: " . ($email ?? 'None') . " | Phone: " . ($phone ?? 'None'));
                continue;
            }

            $adData['original_url'] = $detailUrl;
            $listings[] = $adData;
            $count++;

            // Polite delay to prevent rate limit blocks (100ms - 300ms)
            usleep(rand(100000, 300000));
        }

        Log::info("[WaseetScraper] Finished scraping. Successfully extracted {$count} valid listings.");
        return $listings;
    }

    /**
     * Fetch URL content using cURL with realistic headers and cookies.
     */
    protected function fetchUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, 'NEXT_COUNTRY=EG; NEXT_LOCALE=ar');

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::warning("[WaseetScraper] HTTP request returned code {$httpCode} for URL: {$url}");
            return null;
        }

        return $output;
    }

    /**
     * Parse structured listing data from Next.js serialized state inside HTML.
     */
    public function parseListingDetail(string $html): ?array
    {
        // Recursive regex pattern for balanced curly braces in group 1
        $pattern = '/\\\\"ad\\\\"\s*:\s*(?P<ad_json>\{(?:[^{}]++|(?1))*\})/';

        if (!preg_match($pattern, $html, $matches)) {
            return null;
        }

        $rawJson = $matches['ad_json'];
        $cleaned = str_replace(['\\"', '\\\\'], ['"', '\\'], $rawJson);
        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("[WaseetScraper] Failed to decode JSON block: " . json_last_error_msg());
            return null;
        }

        // Extract required attributes
        $waseetId = $data['id'] ?? null;
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $price = $data['price'] ?? 0;
        $currency = $data['currency'] ?? 'ج.م';
        $phone = $data['phone_number'] ?? null;
        $email = $data['attributes']['email']['value'] ?? null;
        $city = $data['city'] ?? null;

        // Normalize phone number (ensure + or leading digits)
        if ($phone) {
            $phone = trim($phone);
            // If it starts with 201... add '+'
            if (str_starts_with($phone, '201') && strlen($phone) === 12) {
                $phone = '+' . $phone;
            }
        }

        // Clean email
        if ($email) {
            $email = strtolower(trim($email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = null;
            }
        }

        // Map images
        $images = [];
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $img) {
                if (is_array($img)) {
                    $url = $img['url'] ?? $img['image'] ?? $img['thumbnail'] ?? null;
                    if ($url) {
                        $images[] = $url;
                    }
                } elseif (is_string($img)) {
                    $images[] = $img;
                }
            }
        }

        // Fallback to featured image if no gallery images
        if (empty($images) && !empty($data['web_featured_image'])) {
            $featured = $data['web_featured_image'];
            if (is_string($featured) && !str_contains($featured, 'ad-global')) {
                $images[] = $featured;
            }
        }

        return [
            'waseet_id' => $waseetId,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'currency' => $currency,
            'phone' => $phone,
            'email' => $email,
            'city' => $city,
            'images' => $images,
        ];
    }
}
