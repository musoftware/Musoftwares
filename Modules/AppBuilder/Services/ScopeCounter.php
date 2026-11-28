<?php

namespace Modules\AppBuilder\Services;

/** Reads a build brief and says what the site's estimator counts in it: the customer pages and the price-list modules. */
interface ScopeCounter
{
    /** @return array{pages: int, modules: list<string>} */
    public function count(string $projectType, string $spec): array;
}
