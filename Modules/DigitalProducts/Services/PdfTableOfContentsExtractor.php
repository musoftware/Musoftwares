<?php

namespace Modules\DigitalProducts\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfTableOfContentsExtractor
{
    /**
     * Extract chapters and table of contents from a PDF file path.
     *
     * @param string|null $filePath Relative or absolute path to the PDF
     * @param int $productId Optional product ID for caching
     * @return array
     */
    public static function extract(?string $filePath, ?int $productId = null): array
    {
        if (empty($filePath)) {
            return [];
        }

        $cacheKey = $productId ? "digital_product_pdf_toc_{$productId}" : 'digital_product_pdf_toc_' . md5($filePath);

        return Cache::remember($cacheKey, 86400 * 7, function () use ($filePath) {
            $realPath = self::resolveAbsolutePath($filePath);
            if (!$realPath || !file_exists($realPath) || !is_readable($realPath)) {
                return [];
            }

            try {
                // 1. Attempt to extract bookmarks / outlines directly from PDF binary structure
                $outlineChapters = self::extractOutlinesFromPdf($realPath);
                if (count($outlineChapters) >= 2) {
                    return $outlineChapters;
                }

                // 2. Attempt to extract text from early pages (pages 1-10) for TOC lines
                $streamChapters = self::extractChaptersFromStreams($realPath);
                if (count($streamChapters) >= 2) {
                    return $streamChapters;
                }
            } catch (\Throwable $e) {
                // Return empty to allow fallback
            }

            return [];
        });
    }

    /**
     * Resolve file path to an absolute local filesystem path.
     */
    protected static function resolveAbsolutePath(string $path): ?string
    {
        if (file_exists($path)) {
            return $path;
        }

        $storagePath = storage_path('app/' . ltrim($path, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $publicStoragePath = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        $publicPath = public_path(ltrim($path, '/'));
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        return null;
    }

    /**
     * Extract PDF bookmarks (/Outlines and /Title dictionary objects).
     */
    protected static function extractOutlinesFromPdf(string $filePath): array
    {
        $content = @file_get_contents($filePath, false, null, 0, 5000000); // Read up to 5MB
        if (!$content) {
            return [];
        }

        $chapters = [];

        // Match /Title (Text) or /Title <Hex>
        if (preg_match_all('/\/Title\s*(?:\(([^\)]+)\)|<([0-9a-fA-F]+)>)/s', $content, $matches, PREG_SET_ORDER)) {
            $idx = 1;
            foreach ($matches as $match) {
                $rawTitle = '';
                if (!empty($match[1])) {
                    $rawTitle = $match[1];
                } elseif (!empty($match[2])) {
                    $hex = $match[2];
                    $bin = @hex2bin($hex);
                    $rawTitle = $bin ?: $hex;
                }

                $cleanTitle = self::decodePdfString($rawTitle);
                $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));

                // Filter out non-chapter noise (like metadata, copyright, etc.)
                if (mb_strlen($cleanTitle) >= 3 && mb_strlen($cleanTitle) <= 120) {
                    $chapters[] = [
                        'num' => sprintf('%02d', $idx++),
                        'title' => $cleanTitle,
                        'subtopics' => [],
                        'project' => 'محتوى معتمد مستخرج مباشرة من ملف الكتاب'
                    ];
                }

                if (count($chapters) >= 15) {
                    break;
                }
            }
        }

        return $chapters;
    }

    /**
     * Extract chapter patterns from decompressed PDF text streams.
     */
    protected static function extractChaptersFromStreams(string $filePath): array
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return [];
        }

        $rawStreams = '';
        $maxBytes = 3000000; // 3MB
        $data = fread($handle, $maxBytes);
        fclose($handle);

        // Find flate-decoded streams
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $data, $streamMatches)) {
            foreach ($streamMatches[1] as $stream) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed) {
                    $rawStreams .= ' ' . $uncompressed;
                }
            }
        }

        if (empty($rawStreams)) {
            return [];
        }

        // Clean PDF text commands (Tj, TJ, ET, BT)
        $cleanText = '';
        if (preg_match_all('/\((.*?)\)\s*Tj/s', $rawStreams, $tjMatches)) {
            $cleanText .= implode("\n", $tjMatches[1]);
        }
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $rawStreams, $tjArrayMatches)) {
            foreach ($tjArrayMatches[1] as $chunk) {
                if (preg_match_all('/\((.*?)\)/', $chunk, $subStrings)) {
                    $cleanText .= implode('', $subStrings[1]) . "\n";
                }
            }
        }

        $cleanText = self::decodePdfString($cleanText);
        $lines = explode("\n", $cleanText);

        $chapters = [];
        $idx = 1;
        foreach ($lines as $line) {
            $line = trim($line);
            // Search for Chapter / الفصل / الوحدة / Numbered section
            if (preg_match('/^(?:الفصل|المحور|الوحدة|Chapter|Part|\d+[\.\-])\s*[:\-\s]*(.+)$/iu', $line, $cMatch)) {
                $title = trim($cMatch[1]);
                if (mb_strlen($title) >= 4 && mb_strlen($title) <= 100) {
                    $chapters[] = [
                        'num' => sprintf('%02d', $idx++),
                        'title' => $title,
                        'subtopics' => [],
                        'project' => 'محتوى معتمد مستخرج من فهرس الـ PDF'
                    ];
                }
            }

            if (count($chapters) >= 12) {
                break;
            }
        }

        return $chapters;
    }

    /**
     * Decode PDF raw strings, handling Unicode BOM (UTF-16BE), Octal escape codes, and UTF-8.
     */
    protected static function decodePdfString(string $str): string
    {
        // Unescape standard PDF octal / escaped characters
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        $str = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $str);

        // Check for UTF-16BE BOM (\xFE\xFF)
        if (str_starts_with($str, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($str, 2), 'UTF-8', 'UTF-16BE');
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        // Try UTF-16BE without BOM if high density of null bytes
        if (substr_count($str, "\x00") > 2) {
            $converted = @mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }

        return @utf8_encode($str);
    }
}
