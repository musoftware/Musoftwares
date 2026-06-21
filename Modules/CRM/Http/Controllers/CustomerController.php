<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['assignee'])->latest()->paginate(20);

        return Inertia::render('CRM/Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['assignee', 'activities']);
        
        return Inertia::render('CRM/Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('crm.customers.index')->with('success', __('crm.customer_deleted'));
    }

    public function addNote(Request $request, Customer $customer)
    {
        $request->validate([
            'note' => 'required|string|max:10000',
        ]);

        $customer->activities()->create([
            'workspace_id' => $customer->workspace_id,
            'user_id' => auth()->id(),
            'event' => 'note_added',
            'metadata' => [
                'note' => $request->note,
            ],
        ]);

        return redirect()->back()->with('success', __('crm.note_added'));
    }
}
