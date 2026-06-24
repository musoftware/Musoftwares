<?php

namespace Modules\ERP\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Services\Procurement\SupplierService;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $suppliers = $this->supplierService->getAllSuppliers($request->all());
        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $supplier = $this->supplierService->createSupplier($data);
        return response()->json($supplier, 201);
    }

    public function show(string $id)
    {
        $supplier = $this->supplierService->getSupplierById($id);
        return response()->json($supplier);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $supplier = $this->supplierService->getSupplierById($id);
        $supplier = $this->supplierService->updateSupplier($supplier, $data);

        return response()->json($supplier);
    }

    public function destroy(string $id)
    {
        $supplier = $this->supplierService->getSupplierById($id);
        $this->supplierService->deleteSupplier($supplier);

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
