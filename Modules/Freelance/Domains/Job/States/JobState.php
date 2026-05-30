<?php

namespace Modules\Freelance\Domains\Job\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class JobState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Open::class)
            ->allowTransition(Open::class, InProgress::class)
            ->allowTransition(Open::class, Cancelled::class)
            ->allowTransition(InProgress::class, Completed::class)
            ->allowTransition(InProgress::class, Cancelled::class);
    }
}
