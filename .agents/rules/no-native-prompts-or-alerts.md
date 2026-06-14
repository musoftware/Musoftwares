# Rule: No Native Prompts or Alerts

## Problem Statement
Using native browser functions like `prompt()`, `alert()`, or `confirm()` provides a jarring, non-customizable, and unprofessional user experience that breaks the premium design system (Shadcn UI). It also lacks proper accessibility controls, styling parity, and breaks the immersive web app experience.

## Rules & Guidelines

### 1. Prohibition of Native Dialogs
- **Never** use `prompt()` to capture user input.
- **Never** use `alert()` to display information or warnings.
- **Never** use `confirm()` for critical confirmations, unless it is a quick unstyled temporary fallback (though Shadcn Dialogs are strongly preferred).

### 2. Mandatory Use of Shadcn Modal/Dialog
- Always build or use a Shadcn UI `<Dialog>` or `<AlertDialog>` for any user interaction that requires input, warnings, or confirmations.
- The modal must follow the standard design system (e.g., proper `<DialogTitle>`, `<DialogDescription>`, `<DialogFooter>`, and localized strings like `__('general.cancel')` and `__('general.save')`).

### 3. Example of Failure vs Success
- **❌ INCORRECT (Using prompt):**
  ```tsx
  <Button onClick={() => { 
      const status = prompt('Enter job status (done, processing, pending):', 'pending'); 
      if (status) saveStatus(status); 
  }}>
      Update Status
  </Button>
  ```

- **✅ CORRECT (Using Dialog):**
  ```tsx
  const [isOpen, setIsOpen] = useState(false);
  const [status, setStatus] = useState('pending');

  <Button onClick={() => setIsOpen(true)}>Update Status</Button>

  <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent>
          <DialogHeader>
              <DialogTitle>Update Status</DialogTitle>
          </DialogHeader>
          <div className="py-4">
              <PremiumCombobox 
                  value={status} 
                  onChange={setStatus} 
                  options={[{value: 'pending', label: 'Pending'}, ...]} 
              />
          </div>
          <DialogFooter>
              <Button variant="outline" onClick={() => setIsOpen(false)}>Cancel</Button>
              <Button onClick={() => saveStatus(status)}>Save</Button>
          </DialogFooter>
      </DialogContent>
  </Dialog>
  ```



---
