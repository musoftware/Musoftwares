<?php

namespace Modules\Shop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Shop\Services\ProductCsvImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminShopImportController extends Controller
{
    public function store(Request $request, ProductCsvImport $import): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        return back()->with('shop_import', $import->handle($request->file('file')->getRealPath()));
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ProductCsvImport::COLUMNS, ',', '"', '');
            fputcsv($output, ['حذاء رياضي', 'أحذية', '850', '1000', '40|41|42', 'أسود|أبيض', 'وصف المنتج'], ',', '"', '');
            fclose($output);
        }, 'products-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
