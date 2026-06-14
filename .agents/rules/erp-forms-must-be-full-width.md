# Rule: ERP Forms Must Be Full Width

## Problem Statement
Using narrowed containers (e.g., `max-w-xl`, `max-w-2xl`, `max-w-3xl`) for forms in the ERP restricts the working area. ERP forms often have multiple columns, data-heavy inputs, and grid layouts. Artificially shrinking the container wastes screen real estate on desktop screens and leads to cramped, difficult-to-use interfaces that feel less professional compared to full-width enterprise layouts.

## Rules & Guidelines

### 1. No Narrow Container Constraints
- **Never** wrap ERP form pages (like `Create`, `Edit`, or `Settings` pages) in narrow width limits like `max-w-3xl`, `max-w-2xl`, or `max-w-md`.
- Forms should utilize the standard ERP layout width which takes advantage of the full screen width on desktop.

### 2. Standard Container Class
- Always use the `max-w-7xl` or equivalent full-width standard for the main content wrapper of a form.
- **Example**:
  ```tsx
  // ❌ INCORRECT (Too narrow, wastes screen space)
  <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
      <form>...</form>
  </div>
  
  // ✅ CORRECT (Full width / standard ERP width)
  <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <form>...</form>
  </div>
  ```

### 3. Grid Layout Support
- By providing a full-width container (`max-w-7xl`), form inputs should naturally be structured into grid layouts (e.g., `grid-cols-1 md:grid-cols-2` or `lg:grid-cols-3` where appropriate) so that fields span comfortably rather than stretching ridiculously long or piling up vertically in a cramped space.

### 4. Integration with Full-Page Forms Rule
- This rule complements the existing `/full-page-forms` policy. While the `full-page-forms` rule dictates that modals/dialogs must be avoided, this rule ensures that the resulting dedicated pages are visually expansive and utilize the available viewport.



---
