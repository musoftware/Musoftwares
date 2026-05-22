<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRecurringTodoTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_recurring_todo_id',
        'todo_id',
        'unique_id',
    ];

    public function recurringTodo()
    {
        return $this->belongsTo(EmployeeRecurringTodo::class, 'employee_recurring_todo_id');
    }

    public function todo()
    {
        return $this->belongsTo(Todo::class);
    }
}
