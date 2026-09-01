# Rule: Mobile Parity and Extreme UX Simplicity

## Problem Statement
Interfaces that are overly complex or omit features on smaller screens create friction for users who rely on the platform daily. The end user is not a developer; they need straightforward, simple text and layouts. Furthermore, a UI that only works well on desktop but degrades or removes features on mobile forces the user to switch devices, breaking their workflow.

## Rules & Guidelines

### 1. Native-App Quality Mobile Responsiveness
- **Native App Feel**: All UI components and layouts must be fully mobile responsive. The mobile experience should look and feel exactly like a native application screen (e.g., proper padding, touch-friendly tap targets, sticky headers/footers where appropriate, and absolutely no horizontal scrolling).
- **Seamless PC Scaling**: The UI must simultaneously support and scale gracefully to PC screens, utilizing the available space effectively (e.g., expanding grids, sidebars) without feeling empty or stretched.

### 2. Orientation & Adaptive Layouts
- **Horizontal and Portrait Support**: The interface must adapt flawlessly to both horizontal (landscape) and portrait orientations on mobile devices and tablets, ensuring that tables, forms, and grids remain legible and fully functional regardless of how the device is held.
- **Adaptive Layouts over Deletion**: Use adaptive design patterns (e.g., moving secondary actions into "More" dropdown menus, using bottom sheets, or transforming tables into stacked card lists) instead of removing the feature entirely to save space.

### 3. Quick Actions & Fast Workflows for Daily Use
- **Quick Actions**: Every screen must contain immediately accessible "Quick Actions" for the most common tasks (e.g., quick add buttons, context menus, floating action buttons on mobile) so users can perform their jobs rapidly without navigating through multiple pages.
- **Assume Daily Use**: The user will interact with these screens every day. Optimize the UI for high repetition—make primary actions instantly obvious, reduce visual clutter, and ensure workflows are fast and linear.
- **Low Cognitive Load & Fast Jobs**: If an action is performed frequently, it should require the absolute minimum number of clicks, taps, or cognitive effort. Provide clear feedback (success/error states) immediately.

### 4. Extreme Simplicity & 100% Feature Parity
- **100% Feature Parity**: **Never** hide core functionality, buttons, or features on mobile views simply to "save space." Every single function available on the desktop version must be fully accessible and usable on the mobile version.
- **Clear & Simple Text**: Use plain, straightforward, everyday language for labels, buttons, validation errors, and instructions. Do not use technical jargon, developer-speak, or complex terminology. Assume the end user is a regular business person, not a tech genius.


### 5. Clean Mobile Top Navigation Bar (No Top-Bar Buttons)
- **Zero Action Buttons in Top Bar on Mobile**: Never place action buttons (e.g. Sign In, Register, Dashboard, Start Project, Add Balance, Exit, etc.) directly in the mobile top navigation row.
- **Only Inside Hamburger Menu**: All action buttons, links, and navigation items on mobile must be housed exclusively inside the slide-out hamburger menu drawer/sheet (`SheetContent`).
- **Clean Header Header Aesthetic**: On mobile, the top bar should strictly consist of the Logo/Monogram, the Hamburger Menu button, and non-intrusive icons like Theme Toggle, Notification Bell, or Profile Avatar.

---
