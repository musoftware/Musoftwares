<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['roles', 'branch'])->paginate(20);

        return Inertia::render('CRM/Employees/Index', [
            'employees' => $employees,
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['leads', 'customers', 'roles', 'branch']);
        
        return Inertia::render('CRM/Employees/Show', [
            'employee' => $employee,
        ]);
    }
}
