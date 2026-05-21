<?php

namespace App\Helpers;

use App\Models\Freelance\Client;
use App\Models\Freelance\Currency;
use App\Models\Marketplace\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskHelper
{
    protected static $instance = null;
    protected static $support_obj = null;

    public static function instance(): ?TaskHelper
    {
        if (self::$instance === null) {
            self::$instance = new TaskHelper();
        }
        return self::$instance;
    }

    public static function pausedTasksCount()
    {
        $tasks_count = 0;
        foreach (Task::query()->where('archived', '0')->get() as $task) {
            $tasks_count += $task->task_todo_items()->where('paused', '1')->count();
        }
        return $tasks_count;
    }


    public function arrange_tasks()
    {
        $clients = User::query();
        if (request('user')) {
            $clients->where('id', request('user'));
        }
        $clients = $clients->get();

        $tasks = array();
        $sort = array();
        foreach ($clients as $item) {


            $task_lists = $item->tasks()->where('archived', false)->get();
            foreach ($task_lists as $index => $container) {
                $pending_count = $container->task_todo_items()->where('completed', false)->count();
                $tasks[] = $container;
                $sort[] = $pending_count;
            }
        }
        array_multisort($sort, $tasks);
        krsort($tasks);

        $sort_data = array();
        $clients = array();

        foreach ($tasks as $task) {
            $cl = $task->user;
            $clients[$cl->id] = $cl->name;

            if (!isset($sort_data[$cl->id])) {
                $sort_data[$cl->id] = array();
            }
            $sort_data[$cl->id][] = $task;
        }

        return compact('tasks', 'sort_data', 'clients');
    }

}
