<?php

namespace App\Helpers;

class ActionHelper
{
    public static function create_action($user, $action_name)
    {
        return $user->actions()->firstOrCreate([
            'action_name' => $action_name,
        ], [

        ]);
    }

    public static function find_action($user, $action_name)
    {
        return $user->actions()->where('action_name', $action_name)->exists();
    }

    public static function add_action_coins($user, $action_name, $coin)
    {
        $action = ActionHelper::create_action($user, $action_name);
        $action->status = 'completed';
        $action->save();
        $action->increment('coins_reward', $coin); // For example, award 10 coins

        return $action->id;
    }
}
