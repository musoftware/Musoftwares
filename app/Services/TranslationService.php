<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected $client;
    protected $provider;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
        $this->provider = config('services.translation.provider', 'libretranslate');
    }

    /**
     * Translate text from source language to target language
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'auto'): ?string
    {
        // Create cache key
        $cacheKey = "translation:" . md5($text . $sourceLang . $targetLang);
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Check if text is too long (more than 1000 characters)
        // If so, split into chunks and translate separately
        if (mb_strlen($text) > 1000) {
            $translated = $this->translateLongText($text, $targetLang, $sourceLang);
        } else {
            // Try translation with retry and fallback
            $translated = $this->translateWithRetry($text, $targetLang, $sourceLang);
        }

        // Cache for 30 days if successful
        if ($translated) {
            Cache::put($cacheKey, $translated, now()->addDays(30));
        }

        return $translated;
    }

    /**
     * Translate long text by splitting into chunks
     */
    protected function translateLongText(string $text, string $targetLang, string $sourceLang): ?string
    {
        // Split by paragraphs (double newlines) or sentences
        $chunks = $this->splitTextIntoChunks($text, 800);
        
        $translatedChunks = [];
        
        foreach ($chunks as $chunk) {
            if (empty(trim($chunk))) {
                $translatedChunks[] = $chunk;
                continue;
            }
            
            $translated = $this->translateWithRetry($chunk, $targetLang, $sourceLang);
            
            if ($translated) {
                $translatedChunks[] = $translated;
            } else {
                // If a chunk fails, use original
                Log::warning("Failed to translate chunk: " . substr($chunk, 0, 50));
                $translatedChunks[] = $chunk;
            }
            
            // Small delay between chunks to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
        
        return implode('', $translatedChunks);
    }

    /**
     * Split text into chunks by sentences or paragraphs
     */
    protected function splitTextIntoChunks(string $text, int $maxChunkSize = 800): array
    {
        $chunks = [];
        
        // First try to split by double newlines (paragraphs)
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $currentChunk = '';
        
        foreach ($paragraphs as $paragraph) {
            // If adding this paragraph would exceed max size
            if (mb_strlen($currentChunk . $paragraph) > $maxChunkSize && !empty($currentChunk)) {
                // Save current chunk and start new one
                $chunks[] = $currentChunk;
                $currentChunk = $paragraph;
            } else {
                // Add to current chunk
                $currentChunk .= (empty($currentChunk) ? '' : "\n\n") . $paragraph;
            }
            
            // If single paragraph is too long, split by sentences
            if (mb_strlen($currentChunk) > $maxChunkSize) {
                $sentences = preg_split('/([.!?]+\s+)/', $currentChunk, -1, PREG_SPLIT_DELIM_CAPTURE);
                $sentenceChunk = '';
                
                for ($i = 0; $i < count($sentences); $i += 2) {
                    $sentence = $sentences[$i] . ($sentences[$i + 1] ?? '');
                    
                    if (mb_strlen($sentenceChunk . $sentence) > $maxChunkSize && !empty($sentenceChunk)) {
                        $chunks[] = $sentenceChunk;
                        $sentenceChunk = $sentence;
                    } else {
                        $sentenceChunk .= $sentence;
                    }
                }
                
                $currentChunk = $sentenceChunk;
            }
        }
        
        // Add remaining chunk
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }
        
        return $chunks;
    }

    /**
     * Translate with retry mechanism and fallback
     */
    protected function translateWithRetry(string $text, string $targetLang, string $sourceLang, int $maxRetries = 3): ?string
    {
        $providers = ['libretranslate', 'google'];
        
        foreach ($providers as $provider) {
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $translated = match($provider) {
                        'libretranslate' => $this->translateLibre($text, $targetLang, $sourceLang),
                        'google' => $this->translateGoogle($text, $targetLang, $sourceLang),
                        default => null,
                    };

                    if ($translated) {
                        return $translated;
                    }
                } catch (\Exception $e) {
                    Log::warning("Translation attempt {$attempt}/{$maxRetries} failed with {$provider}: " . $e->getMessage());
                    
                    // Wait before retry (exponential backoff)
                    if ($attempt < $maxRetries) {
                        usleep(1000000 * $attempt); // 1s, 2s, 3s
                    }
                }
            }
            
            Log::info("Switching from {$provider} to next provider");
        }

        Log::error("All translation providers failed for text: " . substr($text, 0, 50));
        return null;
    }

    /**
     * LibreTranslate API (Free & Open Source)
     */
    protected function translateLibre(string $text, string $target, string $source): ?string
    {
        $url = config('services.translation.libretranslate_url', 'https://libretranslate.com/translate');
        
        $response = $this->client->post($url, [
            'json' => [
                'q' => $text,
                'source' => $source === 'auto' ? 'auto' : $source,
                'target' => $target,
                'format' => 'text',
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['translatedText'] ?? null;
    }

    /**
     * Google Translate (Unofficial)
     */
    protected function translateGoogle(string $text, string $target, string $source): ?string
    {
        $url = 'https://translate.googleapis.com/translate_a/single';
        
        $response = $this->client->get($url, [
            'query' => [
                'client' => 'gtx',
                'sl' => $source === 'auto' ? 'auto' : $source,
                'tl' => $target,
                'dt' => 't',
                'q' => $text,
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        
        if (isset($data[0])) {
            $translated = '';
            foreach ($data[0] as $sentence) {
                if (isset($sentence[0])) {
                    $translated .= $sentence[0];
                }
            }
            return $translated;
        }

        return null;
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(string $text): string
    {
        // Simple detection based on Arabic characters
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        return 'en';
    }
}
