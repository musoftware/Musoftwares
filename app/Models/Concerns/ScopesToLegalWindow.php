<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\LegalWindowScope;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToLegalWindow
{
    public static function bootScopesToLegalWindow(): void
    {
        static::addGlobalScope(new LegalWindowScope);
    }

    public static function withoutLegalWindow(): Builder
    {
        return static::withoutGlobalScope(LegalWindowScope::class);
    }

    public function getLegalWindowColumn(): string
    {
        return 'created_at';
    }

    public function isLegalWindowDateOnly(): bool
    {
        return false;
    }
}

