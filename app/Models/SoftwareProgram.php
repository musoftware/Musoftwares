<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\TranslationService;

class SoftwareProgram extends Model
{
    use SoftDeletes, HasFactory;



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'download_link',
        'description',
        'image',
    ];

    /**
     * Get translations for this software program
     */
    public function translations()
    {
        return $this->hasMany(SoftwareProgramTranslation::class);
    }

    /**
     * Get translation for a specific field and locale
     */
    public function getTranslation(string $field, string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        // If requesting the original locale, return original (assuming English is default/original for now)
        // Or implement detectLanguage logic if needed like in Service model
        $originalLocale = app(TranslationService::class)->detectLanguage($this->attributes[$field] ?? '');
        if ($locale === $originalLocale) {
            return $this->attributes[$field] ?? null;
        }

        // Check database for existing translation
        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        if ($translation) {
            return $translation->value;
        }

        // Don't auto-translate for unsaved models
        if (!$this->exists || !$this->id) {
            return $this->attributes[$field] ?? null;
        }

        // Auto-translate and cache
        $translationService = app(TranslationService::class);
        $translated = $translationService->translate(
            $this->attributes[$field] ?? '',
            $locale,
            $originalLocale
        );

        if ($translated) {
            SoftwareProgramTranslation::updateOrCreate(
                [
                    'software_program_id' => $this->id,
                    'locale' => $locale,
                    'field' => $field,
                ],
                [
                    'value' => $translated,
                ]
            );
        }

        return $translated ?? ($this->attributes[$field] ?? null);
    }

    /**
     * Get description attribute with automatic translation
     */
    public function getDescriptionAttribute($value)
    {
        if (!$value) return $value;

        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);

        // If current locale matches original, return as-is
        if ($locale === $originalLocale) {
            return $value;
        }

        // Return translated version
        return $this->getTranslation('description', $locale) ?? $value;
    }

    public function description_str()
    {
        return preg_replace("/[\r|\n]{3,}/", "\n\n", $this->description);
    }

}

