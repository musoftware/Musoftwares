<?php

namespace Modules\AppBuilder\Services\Study;

/** Writes the feasibility study. The real one asks Claude; tests give a scripted one. */
interface StudyWriter
{
    /** @return array{html: string, input_tokens: int, output_tokens: int} */
    public function write(string $system, string $brief): array;
}
