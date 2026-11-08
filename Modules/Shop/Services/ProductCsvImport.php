<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Support\OptionList;
use Modules\Shop\Support\UniqueSlug;

/**
 * Adds products from a CSV file. Each new product stays hidden until the admin adds a picture.
 * Rows whose name already exists are skipped, so the same file can be uploaded twice safely.
 */
class ProductCsvImport
{
    public const COLUMNS = ['name', 'category', 'price', 'compare_at_price', 'sizes', 'colors', 'description'];

    private const MAX_REPORTED_PROBLEMS = 20;

    /** @return array{created: int, skipped: int, problems: list<string>} */
    public function handle(string $path): array
    {
        $report = ['created' => 0, 'skipped' => 0, 'problems' => []];

        foreach ($this->rows($path) as $lineNumber => $row) {
            $report = $this->importRow($report, $lineNumber, $row);
        }

        $report['problems'] = array_slice($report['problems'], 0, self::MAX_REPORTED_PROBLEMS);

        return $report;
    }

    /** @return array<int, array<string, string>> rows keyed by their line number in the file */
    private function rows(string $path): array
    {
        $text = $this->readAsUtf8($path);
        $delimiter = $this->delimiter($text);
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $text);
        rewind($stream);

        $header = array_map(fn ($column) => strtolower(trim((string) $column)), fgetcsv($stream, 0, $delimiter, '"', '') ?: []);
        $rows = [];
        for ($row = 2; ($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false; $row++) {
            if ($values !== [null]) {
                $rows[$row] = array_combine($header, array_slice(array_pad($values, count($header), ''), 0, count($header)));
            }
        }
        fclose($stream);

        return $rows;
    }

    /** Excel on Arabic Windows may separate columns with a semicolon. */
    private function delimiter(string $text): string
    {
        $firstLine = strtok($text, "\r\n") ?: '';

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /** Excel may save Arabic as Windows-1256 instead of UTF-8. */
    private function readAsUtf8(string $path): string
    {
        $text = (string) file_get_contents($path);
        $text = str_starts_with($text, "\xEF\xBB\xBF") ? substr($text, 3) : $text;

        return mb_check_encoding($text, 'UTF-8') ? $text : (string) iconv('WINDOWS-1256', 'UTF-8//IGNORE', $text);
    }

    private function importRow(array $report, int $lineNumber, array $row): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
        ]);

        if ($validator->fails()) {
            $report['problems'][] = 'السطر '.$lineNumber.': '.$validator->errors()->first();

            return $report;
        }

        if (ShopProduct::where('name', trim($row['name']))->exists()) {
            $report['skipped']++;

            return $report;
        }

        $this->createProduct($row);
        $report['created']++;

        return $report;
    }

    private function createProduct(array $row): void
    {
        $name = trim($row['name']);
        $compareAt = trim($row['compare_at_price'] ?? '');

        ShopProduct::create([
            'category_id' => $this->categoryId(trim($row['category'] ?? '')),
            'name' => $name,
            'slug' => UniqueSlug::make(ShopProduct::class, $name),
            'description' => trim($row['description'] ?? '') ?: null,
            'price' => $row['price'],
            'compare_at_price' => $compareAt === '' ? null : $compareAt,
            'sizes' => OptionList::parse($row['sizes'] ?? ''),
            'colors' => OptionList::parse($row['colors'] ?? ''),
            'images' => [],
            'in_stock' => true,
            'is_active' => false,
        ]);
    }

    private function categoryId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return ShopCategory::firstOrCreate(['name' => $name], ['slug' => UniqueSlug::make(ShopCategory::class, $name)])->id;
    }
}
