<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

class AdminAuditLog
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    public $id = null;
    public $actor_user_id;
    public $actor_ip;
    public $actor_user_agent;
    public $action;
    public $severity;
    public $target_type;
    public $target_id;
    public $meta;
    public $created_at;

    public static array $logs = [];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
        $this->created_at ??= now();
    }

    public static function create(array $attributes)
    {
        Log::info("Admin Audit [{$attributes['action']}]: " . json_encode($attributes));

        $instance = new self($attributes);
        self::$logs[] = $instance;

        return $instance;
    }

    public static function where(string $column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return new class($column, $operator, $value) {
            protected array $filtered = [];

            public function __construct($column, $operator, $value)
            {
                $this->filtered = array_filter(AdminAuditLog::$logs, function ($log) use ($column, $operator, $value) {
                    $itemVal = $log->{$column} ?? null;
                    if ($operator === '=') {
                        return $itemVal == $value;
                    }
                    return false;
                });
            }

            public function latest($column = 'id')
            {
                return $this;
            }

            public function first()
            {
                return reset($this->filtered) ?: null;
            }
        };
    }
}
