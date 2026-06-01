<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('erp::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('ERP/Expenses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', __('general.expense_logged_successfully'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('erp::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('erp::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
