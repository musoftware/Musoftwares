# Service Class Patterns

## Purpose
Decouples business logic from HTTP Controllers to allow reuse in console commands, jobs, and other controllers.

## Examples
```php
class InvoiceService {
    public function generatePdf(Invoice $invoice) { ... }
    public function calculateTotals(Invoice $invoice) { ... }
}
```
