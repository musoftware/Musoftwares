<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LanguageLine extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'text'];

    protected $casts = [
        'text' => 'array',
    ];

    /**
     * Get the translation for a specific locale
     */
    public function getTranslation(string $locale): ?string
    {
        return $this->text[$locale] ?? null;
    }

    /**
     * Set the translation for a specific locale
     */
    public function setTranslation(string $locale, string $value): self
    {
        $text = $this->text;
        $text[$locale] = $value;
        $this->text = $text;

        return $this;
    }

    /**
     * Get all translations for a group and locale
     */
    public static function getTranslationsForGroup(string $locale, string $group): array
    {
        return Cache::rememberForever("translations.{$locale}.{$group}", function () use ($locale, $group) {
            return static::query()
                ->where('group', $group)
                ->get()
                ->reduce(function ($translations, LanguageLine $languageLine) use ($locale) {
                    $translations[$languageLine->key] = $languageLine->getTranslation($locale);
                    return $translations;
                }, []);
        });
    }

    /**
     * Flush translation cache
     */
    public static function flushCache()
    {
        Cache::tags(['translations'])->flush(); // If using tags
        // Since we might not be using tags, we'll need a better way if we want to be specific
        // For now, simple clear or specific keys
    }

    protected static function booted()
    {
        static::saved(function ($model) {
            foreach (array_keys($model->text) as $locale) {
                Cache::forget("translations.{$locale}.{$model->group}");
            }
        });

        static::deleted(function ($model) {
            foreach (array_keys($model->text) as $locale) {
                Cache::forget("translations.{$locale}.{$model->group}");
            }
        });
    }
}
