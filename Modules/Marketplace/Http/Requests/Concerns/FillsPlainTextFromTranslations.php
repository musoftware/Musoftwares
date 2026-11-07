<?php

namespace Modules\Marketplace\Http\Requests\Concerns;

/** The plain column holds the English text, or the Arabic text when there is no English. */
trait FillsPlainTextFromTranslations
{
    protected function fillPlainTextFromTranslations(): void
    {
        $plain = [];
        foreach (['title', 'tagline', 'description', 'auto_reply'] as $field) {
            $translations = $this->input("{$field}_translations");
            if (is_array($translations)) {
                $plain[$field] = ($translations['en'] ?? null) ?: (($translations['ar'] ?? null) ?: null);
            }
        }

        $this->merge($plain);
    }
}
