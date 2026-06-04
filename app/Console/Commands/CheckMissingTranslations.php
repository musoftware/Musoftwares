<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CheckMissingTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:check {--write : Whether to add missing keys to language files with empty values} {--report : Export a JSON report of the missing translations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for missing translation keys across the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning application for translation keys...');

        $directories = [
            resource_path('views'),
            resource_path('js'),
            app_path(),
            base_path('Modules'),
        ];

        $extensions = ['php', 'js', 'jsx', 'ts', 'tsx', 'vue'];
        
        $foundKeys = [];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                continue;
            }

            $files = File::allFiles($directory);

            foreach ($files as $file) {
                if (!in_array($file->getExtension(), $extensions)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                // Match __('general.key') or trans('general.key') or @lang('key')
                preg_match_all("/(?:__|trans|@lang)\(\s*['\"]([^'\"]+)['\"]\s*\)/U", $content, $matches);

                if (!empty($matches[1])) {
                    foreach ($matches[1] as $key) {
                        // Skip variables or dynamic keys like 'erp.'.$section
                        if (str_contains($key, '$') || str_contains($key, '{')) {
                            continue;
                        }
                        $foundKeys[$key] = true;
                    }
                }
            }
        }

        $foundKeys = array_keys($foundKeys);
        sort($foundKeys);

        $this->info("Found " . count($foundKeys) . " unique translation keys used in the codebase.");

        $locales = ['en', 'ar'];
        $missing = [];

        foreach ($locales as $locale) {
            $langPath = base_path("lang/{$locale}");
            $definedKeys = [];

            if (File::isDirectory($langPath)) {
                $langFiles = File::allFiles($langPath);
                foreach ($langFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $group = str_replace('.php', '', $file->getFilename());
                        $translations = require $file->getPathname();
                        
                        $flattened = \Illuminate\Support\Arr::dot($translations);
                        foreach ($flattened as $k => $v) {
                            $definedKeys["{$group}.{$k}"] = true;
                        }
                    }
                }
            }

            // Also check root lang JSON files
            $jsonFile = base_path("lang/{$locale}.json");
            if (File::exists($jsonFile)) {
                $translations = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($translations as $k => $v) {
                    $definedKeys[$k] = true;
                }
            }

            $missing[$locale] = [];

            foreach ($foundKeys as $key) {
                // If the key has no dot, it might be in JSON file or global
                if (!isset($definedKeys[$key])) {
                    // Sometimes keys are just plain text without group, like __('general.hello')
                    $missing[$locale][] = $key;
                }
            }
        }

        $hasMissing = false;
        foreach ($locales as $locale) {
            if (count($missing[$locale]) > 0) {
                $hasMissing = true;
                $this->warn("\nMissing translations for locale: [{$locale}]");
                foreach ($missing[$locale] as $key) {
                    $this->line("- {$key}");
                }
            } else {
                $this->info("\nNo missing translations for locale: [{$locale}]");
            }
        }

        if (!$hasMissing) {
            $this->info("\nAll good! No missing translations found.");
            return 0;
        }

        if ($this->option('report')) {
            $reportPath = storage_path('logs/missing_translations_report.json');
            
            $reportData = [
                'locales_checked' => $locales,
                'total_keys_found' => count($foundKeys),
                'missing_by_locale' => $missing,
                'key_locations' => array_flip($foundKeys) // we need the locations
            ];
            
            file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nReport generated successfully: {$reportPath}");
        }

        if ($this->option('write')) {
            $this->info("\nWriting missing keys to files...");
            $this->writeMissingKeys($missing);
        }

        return 1;
    }

    protected function writeMissingKeys($missing)
    {
        foreach ($missing as $locale => $keys) {
            $langPath = base_path("lang/{$locale}");
            
            $grouped = [];
            $jsonKeys = [];

            foreach ($keys as $key) {
                // If key has spaces or doesn't have a dot, it belongs in the JSON file
                if (str_contains($key, ' ') || !str_contains($key, '.')) {
                    $jsonKeys[$key] = $key;
                } else {
                    [$group, $item] = explode('.', $key, 2);
                    $grouped[$group][$item] = $item;
                }
            }

            if (!empty($jsonKeys)) {
                $jsonFile = base_path("lang/{$locale}.json");
                $existing = [];
                if (File::exists($jsonFile)) {
                    $existing = json_decode(file_get_contents($jsonFile), true) ?: [];
                }
                $merged = array_merge($jsonKeys, $existing);
                file_put_contents($jsonFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info("Updated lang/{$locale}.json");
            }

            foreach ($grouped as $group => $items) {
                $file = "{$langPath}/{$group}.php";
                $existing = [];
                if (File::exists($file)) {
                    $existing = require $file;
                } else {
                    if (!File::isDirectory($langPath)) {
                        File::makeDirectory($langPath, 0755, true);
                    }
                }
                
                // Expand dot notation back to nested arrays for merging
                $expandedItems = [];
                foreach ($items as $k => $v) {
                    \Illuminate\Support\Arr::set($expandedItems, $k, $v);
                }

                $merged = array_replace_recursive($expandedItems, $existing);
                
                $content = "<?php\n\nreturn " . $this->varExport54($merged) . ";\n";
                file_put_contents($file, $content);
                $this->info("Updated lang/{$locale}/{$group}.php");
            }
        }
    }

    /**
     * Export array to PHP syntax string
     */
    protected function varExport54($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return "'" . addcslashes($var, "\\\$\"\'\r\n\t\v\f") . "'";
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : $this->varExport54($key) . " => ")
                        . $this->varExport54($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "true" : "false";
            default:
                return var_export($var, true);
        }
    }
}
