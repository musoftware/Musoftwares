---
trigger: always_on
glob: "**/*.{ts,tsx,js,jsx,blade.php,vue}"
description: Enforces mobile-first responsive design parity with PC, and demands extreme UX simplicity for non-technical daily users.
---

# Rule: Mobile Parity and Extreme UX Simplicity

## Problem Statement
Interfaces that are overly complex or omit features on smaller screens create friction for users who rely on the platform daily. The end user is not a developer; they need straightforward, simple text and layouts. Furthermore, a UI that only works well on desktop but degrades or removes features on mobile forces the user to switch devices, breaking their workflow.

## Rules & Guidelines

### 1. Native-App Quality Mobile Responsiveness
- **Native App Feel**: All UI components and layouts must be fully mobile responsive. The mobile experience should look and feel exactly like a native application screen (e.g., proper padding, touch-friendly tap targets, sticky headers/footers where appropriate, and absolutely no horizontal scrolling).
- **Seamless PC Scaling**: The UI must simultaneously support and scale gracefully to PC screens, utilizing the available space effectively (e.g., expanding grids, sidebars) without feeling empty or stretched.

### 2. 100% Feature Parity Across All Screens
- **No Hidden Functions**: **Never** hide core functionality, buttons, or features on mobile views simply to "save space." Every single function available on the desktop version must be fully accessible and usable on the mobile version.
- **Adaptive Layouts over Deletion**: Use adaptive design patterns (e.g., moving secondary actions into "More" dropdown menus, using bottom sheets, or transforming tables into stacked card lists) instead of removing the feature entirely.

### 3. Extreme Simplicity for Daily Users
- **Assume Daily Use**: The user will interact with these screens every day. Optimize the UI for high repetition—make primary actions instantly obvious, reduce visual clutter, and ensure workflows are fast and linear.
- **Clear & Simple Text**: Use plain, straightforward, everyday language for labels, buttons, validation errors, and instructions. Do not use technical jargon, developer-speak, or complex terminology. Assume the end user is a regular business person, not a tech genius.
- **Low Cognitive Load**: If an action is performed frequently, it should require the absolute minimum number of clicks, taps, or cognitive effort. Provide clear feedback (success/error states) immediately.
