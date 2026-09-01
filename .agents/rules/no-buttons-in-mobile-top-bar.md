# Rule: No Action Buttons in Mobile Top Navigation Bars (Only Inside Hamburger Menu)

## Problem Statement
Placing CTA buttons, auth buttons, or direct action pills (such as "Sign in", "Register", "Start a Project", "Dashboard", "Add Balance", "Console", or "Exit") directly in the top header bar on mobile viewports causes horizontal overflow, line-wrapping, and severe visual clutter.

## Rules & Guidelines

### 1. Clean Mobile Top Navigation Bar
- **No Action Buttons Outside Hamburger Menu**: On mobile viewports (`< md` or `< lg`), NEVER render action buttons, pills, or text links in the visible top header row.
- **Allowed Mobile Header Elements**: The mobile top bar must only contain:
  1. The brand Logo/Monogram and site name.
  2. The Hamburger menu toggle button (`Menu` / `SheetTrigger`).
  3. Non-intrusive utility icons if applicable (e.g., ThemeToggle, Notification Bell, User Avatar).
- Use responsive classes like `hidden md:flex`, `hidden lg:flex`, or `hidden sm:inline-flex` for all top-bar action buttons.

### 2. 100% Feature Access Inside Hamburger Drawer
- All action buttons, navigation links, and utilities that exist on desktop MUST be accessible inside the mobile hamburger menu drawer / sheet.
- Buttons inside the hamburger drawer must have touch-friendly tap targets (minimum 44px height) and clean typography.
- Never remove or sacrifice functionality on mobile—relocate it into the hamburger drawer.

### 3. Universal Application
This rule applies across all layouts and templates in the codebase:
- `PublicLayout.tsx`
- `AuthenticatedLayout.tsx`
- `WebToolsLayout.tsx`
- `ERPLayout.tsx`
- `CrmLayout.tsx`
- `AdminSidebarLayout.tsx`
- `WorkspaceLayout.tsx`
