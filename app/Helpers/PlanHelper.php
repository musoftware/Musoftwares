<?php

namespace App\Helpers;

class PlanHelper
{
    public static function buttonText()
    {
        if (empty(\auth()->user()->subscription_plan)) {
            return 'Choose Plan';
        } else {
            return 'Upgrade';
        }
    }
}
