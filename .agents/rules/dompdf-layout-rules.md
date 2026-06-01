---
description: "Rules for designing and fixing DomPDF Blade templates to prevent horizontal overflow and text cutoff."
---

# Rule: DomPDF Layout & Overflow Prevention

## Problem Statement
When designing PDF templates (like Invoices or Receipts) using `barryvdh/laravel-dompdf` (DomPDF engine), applying standard CSS properties like `width: 100%` alongside `padding` inside the main `.container` or `body` elements causes severe horizontal overflow. DomPDF does not fully respect `box-sizing: border-box`, meaning the total width becomes `100% + padding_left + padding_right`. This results in the right side of the invoice (e.g., total amounts, right-aligned text) being completely cut off from the final generated PDF.

## Rules & Guidelines

### 1. Prohibition of `width: 100%` on Padded Containers
- **Never** apply `width: 100%` to a main wrapping container (like `.container`, `.wrapper`, or `body`) if that container also has `padding`.
- **Example of Failure:**
  ```css
  /* ❌ INCORRECT (Will push right-side content off the page in DomPDF) */
  .container {
      width: 100%;
      padding: 48px;
      box-sizing: border-box; /* Ignored or poorly supported by DomPDF */
  }
  ```

### 2. Correct Approach for Spacing and Width
- Let block-level elements naturally fill the space without an explicit width, or rely entirely on `@page` margins instead of container padding.
- **Example of Success:**
  ```css
  /* ✅ CORRECT (Block elements natively adapt to the page bounds without overflow) */
  .container {
      padding: 48px; /* Natural block expansion prevents overflow */
  }
  ```

### 3. Full-Width Tables
- For tables (e.g., `.items-table`, `.header-table`), you may use `width: 100%;` because tables correctly calculate column widths within the bounds of their parent container.
- Do not apply horizontal padding directly to a table that has `width: 100%`. Apply padding to the `th` and `td` elements instead.

### 4. Floating Elements
- When using `float: right;` (e.g., for the totals section), ensure it has a fixed width or `width: auto;` to prevent layout collapse. It will align correctly against the right edge of the parent container as long as the parent is not overflowing.

### 5. Summary Checklist
- [ ] Have I ensured that no `.container` combines `width: 100%` with horizontal `padding`?
- [ ] Is `box-sizing: border-box;` avoided or not relied upon for core structural elements in DomPDF views?
