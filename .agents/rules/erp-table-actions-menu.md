---
trigger: always_on
glob: "**/*.{tsx,jsx,blade.php}"
description: Enforce the use of a single Action Modal for data table rows instead of displaying multiple individual action buttons.
---

# Rule: Single Action Menu for Data Tables (No Cluttered Buttons)

## Problem Statement
Displaying multiple action buttons (e.g., Edit, Delete, View, Duplicate) directly inside a data table row clutters the UI, causes horizontal scrolling, and creates a poor User Experience (UX), especially on smaller screens. For example, rendering 4-5 buttons per row makes the table look very busy and violates the clean, simple UX principles of the ERP module.

---

## Rules & Guidelines

### 1. Prohibition of Multiple Inline Action Buttons
- **Never** render multiple separate action buttons directly within a data table cell.
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Too many buttons per row)
  <TableCell>
      <Button variant="ghost">Edit</Button>
      <Button variant="ghost">View</Button>
      <Button variant="destructive">Delete</Button>
      <Button variant="outline">Print</Button>
  </TableCell>
  ```

### 2. Mandatory Single Action Menu (Modal)
- Always use a single action button (typically an ellipsis `...` or a generic "Actions" button) that opens an Action Modal containing all the row-specific actions.
- This pattern must be used for any data table that has 2 or more actions per row.
- **Example of Correct Pattern (using Shadcn UI Dialog)**:
  ```tsx
  // ✅ CORRECT (Single button opening an action modal)
  <TableCell>
      <Dialog>
          <DialogTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
              </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-xs">
              <DialogHeader>
                  <DialogTitle>Actions</DialogTitle>
              </DialogHeader>
              <div className="flex flex-col gap-2 py-2">
                  <Button variant="outline" className="justify-start" onClick={() => handleView(row.id)}>
                      View
                  </Button>
                  <Button variant="outline" className="justify-start" onClick={() => handleEdit(row.id)}>
                      Edit
                  </Button>
                  <Button variant="destructive" className="justify-start" onClick={() => handleDelete(row.id)}>
                      Delete
                  </Button>
              </div>
          </DialogContent>
      </Dialog>
  </TableCell>
  ```

### 3. Mobile Responsiveness and Parity
- Condensing actions into a single menu ensures that the table fits gracefully on mobile devices and smaller screens without truncating important controls.
- The action menu provides 100% feature parity while maintaining a simple, clean UI.

### 4. Summary Checklist
- [ ] Are there multiple action buttons taking up horizontal space in the data table row?
- [ ] Have the actions been consolidated into a single Action Modal?
- [ ] Is the action menu fully accessible (e.g., screen-reader friendly and keyboard navigable)?
