<?php

namespace Modules\Marketplace\Http\Requests\Concerns;

/** The seller form sends the buyer requirements as text, one per line. The column holds a list. */
trait NormalizesRequirements
{
    protected function prepareForValidation(): void
    {
        $requirements = $this->input('requirements');

        if (is_string($requirements)) {
            $lines = array_filter(array_map('trim', preg_split('/\R/u', $requirements) ?: []));
            $this->merge(['requirements' => array_values($lines)]);
        }
    }
}
