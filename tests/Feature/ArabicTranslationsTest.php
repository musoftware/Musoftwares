<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/** Arabic strings the site shows must be real Arabic, not half machine-translated ("إنشاء your first campaign"). */
class ArabicTranslationsTest extends TestCase
{
    public function test_used_arabic_strings_have_no_english_sentences_left(): void
    {
        $code = '';
        foreach ((new Finder)->files()->in([resource_path('js'), resource_path('views'), app_path(), base_path('Modules')])
            ->exclude(['lang', 'vendor', 'node_modules'])->name(['*.php', '*.js', '*.jsx', '*.ts', '*.tsx']) as $file) {
            $code .= $file->getContents();
        }

        $bad = [];
        foreach (glob(lang_path('ar/*.php')) as $path) {
            $group = basename($path, '.php');
            foreach (Arr::dot(require $path) as $key => $value) {
                if (! is_string($value) || ! preg_match('/\p{Arabic}/u', $value) || ! str_contains($code, "{$group}.{$key}")) {
                    continue;
                }
                // three lowercase English words in a row means an untranslated phrase; brand names and acronyms are fine
                if (preg_match('/\b[a-z]{2,} [a-z]{2,} [a-z]{2,}\b/', strip_tags($value))) {
                    $bad[] = "{$group}.{$key}: {$value}";
                }
            }
        }

        $this->assertSame([], $bad);
    }
}
