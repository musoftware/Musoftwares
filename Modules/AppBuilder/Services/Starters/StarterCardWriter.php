<?php

namespace Modules\AppBuilder\Services\Starters;

/** Turns a delivered project's brief into a short internal card that says nothing about the customer it was built for. */
interface StarterCardWriter
{
    /** @return array{title: string, summary: string, features: list<string>} */
    public function write(string $brief): array;
}
