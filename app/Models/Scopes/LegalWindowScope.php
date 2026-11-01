<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\LegalWindow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class LegalWindowScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $column = method_exists($model, 'getLegalWindowColumn')
            ? $model->getLegalWindowColumn()
            : 'created_at';

        $dateOnly = method_exists($model, 'isLegalWindowDateOnly')
            && $model->isLegalWindowDateOnly();

        $cutoff = $dateOnly
            ? LegalWindow::cutoffDateString()
            : LegalWindow::cutoffDatabase();

        $builder->where($model->qualifyColumn($column), '>=', $cutoff);
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutLegalWindow', function (Builder $query): Builder {
            return $query->withoutGlobalScope(self::class);
        });
    }
}
