# Rule: No Badges on Titles

## Problem Statement
Using badges or "pill" style labels (e.g., `inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3`) directly above, below, or next to page titles creates visual clutter. It often feels redundant, over-designed, and detracts from the clean, simple UX principles of the application.

## Rules & Guidelines

### 1. Prohibition of Title Badges
- **Never** add badge elements around main page titles (`<h1>`, page headers, or section headers) to denote "status", "category", or "feature type".
- Do not use `inline-flex` pill elements with background colors, borders, and rounded corners near titles.
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Using a badge near the title)
  <div>
      <span className="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
          Beta Feature
      </span>
      <h1 className="text-2xl font-bold">Dashboard</h1>
  </div>
  ```

### 2. Clean Typography Focus
- Rely on clean typography and standard spacing for page headers.
- The title should speak for itself.
- **Example of Correct Pattern**:
  ```tsx
  // ✅ CORRECT (Clean title without badges)
  <div>
      <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p className="text-sm text-gray-500 mt-1">Overview of your business metrics.</p>
  </div>
  ```

### 3. Alternative Placement for Statuses
- If a status must be communicated, place it inside the data table row (e.g., a "Paid" status for an invoice), within a dedicated status card, or inside a details panel. Do not attach it to the primary page title.

### 4. Summary Checklist
- [ ] Have all unnecessary badges (e.g. `bg-indigo-50 rounded-full`) been removed from page titles and section headers?
- [ ] Is the page header clean, relying on typography rather than heavy pill-shaped badges?



---
