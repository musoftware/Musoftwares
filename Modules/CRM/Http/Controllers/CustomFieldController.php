<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CustomField;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomFieldController extends Controller
{
    public function index()
    {
        $fields = CustomField::latest()->paginate(20);

        return Inertia::render('CRM/CustomFields/Index', [
            'fields' => $fields,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,date,select,boolean',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'target_model' => 'required|string|in:lead,customer',
        ]);

        CustomField::create($validated);

        return redirect()->back()->with('success', __('crm.custom_field_created'));
    }

    public function update(Request $request, CustomField $customField)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,date,select,boolean',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
        ]);

        $customField->update($validated);

        return redirect()->back()->with('success', __('crm.custom_field_updated'));
    }

    public function destroy(CustomField $customField)
    {
        $customField->delete();
        return redirect()->back()->with('success', __('crm.custom_field_deleted'));
    }
}
