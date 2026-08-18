<?php

namespace Modules\DigitalProducts\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfProcessingService
{
    /**
     * Store the uploaded PDF file securely in local storage.
     */
    public function storePdf(UploadedFile $file): array
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('books/files', $filename, 'local');

        $fullPath = Storage::disk('local')->path($path);
        $fileSize = $file->getSize();

        // Extract metadata using pure PHP
        $metadata = $this->extractPdfMetadataPurePhp($fullPath);

        return [
            'file_path' => $path,
            'file_size' => $fileSize,
            'page_count' => $metadata['page_count'] ?? null,
            'title' => $metadata['title'] ?? null,
            'author' => $metadata['author'] ?? null,
        ];
    }

    /**
     * Store cover image from uploaded base64 data (extracted via PDF.js on client side) or direct uploaded file.
     */
    public function storeCoverImage(string|UploadedFile|null $coverInput): ?string
    {
        if (!$coverInput) {
            return null;
        }

        $coverDir = public_path('storage/books/covers');
        if (!File::exists($coverDir)) {
            File::makeDirectory($coverDir, 0755, true);
        }

        if ($coverInput instanceof UploadedFile) {
            $ext = $coverInput->getClientOriginalExtension() ?: 'webp';
            $filename = 'cover_' . Str::uuid() . '.' . $ext;
            $coverInput->move($coverDir, $filename);
            return 'storage/books/covers/' . $filename;
        }

        if (is_string($coverInput) && Str::startsWith($coverInput, 'data:image')) {
            // Base64 data URL from PDF.js Canvas
            $parts = explode(',', $coverInput, 2);
            if (count($parts) === 2) {
                $imageData = base64_decode($parts[1]);
                $filename = 'cover_' . Str::uuid() . '.webp';
                File::put($coverDir . '/' . $filename, $imageData);
                return 'storage/books/covers/' . $filename;
            }
        }

        return null;
    }

    /**
     * Pure PHP PDF stream parser for page count and basic metadata.
     */
    public function extractPdfMetadataPurePhp(string $filePath): array
    {
        $result = [
            'page_count' => null,
            'title' => null,
            'author' => null,
        ];

        if (!File::exists($filePath)) {
            return $result;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return $result;
        }

        // 1. Extract Page Count via /Count
        if (preg_match_all('/\/Count\s+(\d+)/', $content, $matches)) {
            $counts = array_map('intval', $matches[1]);
            $result['page_count'] = max($counts);
        }

        // Fallback: count /Type /Page (excluding /Pages)
        if (!$result['page_count']) {
            $pageMatches = preg_match_all('/\/Type\s*\/Page[^s]/', $content, $dummy);
            if ($pageMatches > 0) {
                $result['page_count'] = $pageMatches;
            }
        }

        // 2. Extract Title from PDF /Title (...)
        if (preg_match('/\/Title\s*\((.*?)\)/s', $content, $titleMatch)) {
            $title = trim($titleMatch[1]);
            if (!empty($title)) {
                $result['title'] = $this->decodePdfString($title);
            }
        }

        // 3. Extract Author from PDF /Author (...)
        if (preg_match('/\/Author\s*\((.*?)\)/s', $content, $authorMatch)) {
            $author = trim($authorMatch[1]);
            if (!empty($author)) {
                $result['author'] = $this->decodePdfString($author);
            }
        }

        return $result;
    }

    private function decodePdfString(string $str): string
    {
        // Handle PDF octal and unicode escapes if present
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        // Remove backslash escapes
        return str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
    }
}
