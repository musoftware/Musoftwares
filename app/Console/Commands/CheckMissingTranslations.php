<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
            if (! File::isDirectory($directory)) {
                continue;
            }

            $files = File::allFiles($directory);

            foreach ($files as $file) {
                // Ignore node_modules, vendor, or dist directories
                $pathname = $file->getPathname();
                if (str_contains($pathname, 'node_modules') || str_contains($pathname, 'vendor') || str_contains($pathname, 'dist')) {
                    continue;
                }

                if (! in_array($file->getExtension(), $extensions)) {
                    continue;
                }

                $content = file_get_contents($pathname);

                // Match __('group.key', ...), trans('group.key', ...), @lang('group.key', ...), Lang::get('group.key', ...)
                // Group & key must contain a dot (e.g. general.need_more_balance)
                preg_match_all("/(?:__|trans|@lang|Lang::get)\(\s*['\"]([a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-\.]+)['\"]/i", $content, $matches);

                if (! empty($matches[1])) {
                    foreach ($matches[1] as $key) {
                        // Skip variables, incomplete keys, or dynamic keys
                        if (str_contains($key, '$') || str_contains($key, '{') || str_ends_with($key, '.')) {
                            continue;
                        }
                        $foundKeys[$key] = true;
                    }
                }
            }
        }

        $foundKeysList = array_keys($foundKeys);
        sort($foundKeysList);

        $this->info('Found '.count($foundKeysList).' unique translation keys used in the codebase.');

        $locales = ['en', 'ar'];
        $definedKeys = [];

        foreach ($locales as $locale) {
            $langPath = base_path("lang/{$locale}");
            $definedKeys[$locale] = [];

            if (File::isDirectory($langPath)) {
                $langFiles = File::allFiles($langPath);
                foreach ($langFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $group = str_replace('.php', '', $file->getFilename());
                        $translations = require $file->getPathname();

                        if (is_array($translations)) {
                            $flattened = Arr::dot($translations);
                            foreach ($flattened as $k => $v) {
                                $definedKeys[$locale]["{$group}.{$k}"] = true;
                            }
                        }
                    }
                }
            }
        }

        // Collect all target keys: scanned in codebase + defined in any locale (for locale parity check)
        $allTargetKeys = array_unique(array_merge(
            $foundKeysList,
            array_keys($definedKeys['en'] ?? []),
            array_keys($definedKeys['ar'] ?? [])
        ));
        sort($allTargetKeys);

        $missing = [];
        foreach ($locales as $locale) {
            $missing[$locale] = [];
            foreach ($allTargetKeys as $key) {
                if (! isset($definedKeys[$locale][$key])) {
                    $missing[$locale][] = $key;
                }
            }
        }

        $hasMissing = false;
        foreach ($locales as $locale) {
            if (count($missing[$locale]) > 0) {
                $hasMissing = true;
                $this->warn("\nMissing translations for locale: [{$locale}] (".count($missing[$locale])." missing)");
                foreach ($missing[$locale] as $key) {
                    $this->line("- {$key}");
                }
            } else {
                $this->info("\nNo missing translations for locale: [{$locale}]");
            }
        }

        if (! $hasMissing) {
            $this->info("\nAll good! No missing translations found.");

            return 0;
        }

        if ($this->option('report')) {
            $reportPath = storage_path('logs/missing_translations_report.json');

            $reportData = [
                'locales_checked' => $locales,
                'total_keys_scanned' => count($foundKeysList),
                'missing_by_locale' => $missing,
            ];

            file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nReport generated successfully: {$reportPath}");
        }

        if ($this->option('write')) {
            $this->info("\nWriting missing keys to PHP language files...");
            $this->writeMissingKeys($missing);
        }

        return 1;
    }

    protected function writeMissingKeys($missing)
    {
        foreach ($missing as $locale => $keys) {
            if (empty($keys)) {
                continue;
            }

            $langPath = base_path("lang/{$locale}");
            $grouped = [];

            foreach ($keys as $key) {
                if (str_contains($key, '.')) {
                    [$group, $item] = explode('.', $key, 2);
                } else {
                    $group = 'general';
                    $item = $key;
                }

                // Create a humanized default value
                $defaultValue = Str::headline($item);
                $grouped[$group][$item] = $defaultValue;
            }

            foreach ($grouped as $group => $items) {
                $file = "{$langPath}/{$group}.php";
                $existing = [];
                if (File::exists($file)) {
                    $existing = require $file;
                    if (! is_array($existing)) {
                        $existing = [];
                    }
                } else {
                    if (! File::isDirectory($langPath)) {
                        File::makeDirectory($langPath, 0755, true);
                    }
                }

                $updatedCount = 0;
                foreach ($items as $itemKey => $defaultValue) {
                    if (! Arr::has($existing, $itemKey)) {
                        Arr::set($existing, $itemKey, $defaultValue);
                        $updatedCount++;
                    }
                }

                if ($updatedCount > 0) {
                    ksort($existing);
                    $content = "<?php\n\nreturn ".$this->varExport54($existing).";\n";
                    file_put_contents($file, $content);
                    $this->info("Updated lang/{$locale}/{$group}.php (+{$updatedCount} keys)");
                }
            }
        }
    }

    /**
     * Export array to PHP syntax string
     */
    protected function varExport54($var, $indent = '')
    {
        switch (gettype($var)) {
            case 'string':
                return "'".addcslashes($var, "\\\$\"\'\r\n\t\v\f")."'";
            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        .($indexed ? '' : $this->varExport54($key).' => ')
                        .$this->varExport54($value, "$indent    ");
                }

                return "[\n".implode(",\n", $r)."\n".$indent.']';
            case 'boolean':
                return $var ? 'true' : 'false';
            default:
                return var_export($var, true);
        }
    }
}

