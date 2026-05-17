# Styling & CSS Architecture Guide

## Overview

The ERP System enforces a strict **Utility-First Styling Architecture** using **Tailwind CSS v4** (`@tailwindcss/vite`) paired with **shadcn/ui** design tokens. To ensure complete consistency across all modules and prevent CSS bloat, inline custom CSS styles and external CSS-in-JS libraries are strictly prohibited.

## CSS Layering & Tokens

All application styles flow through our root CSS setup using modern CSS variables for design tokens:

```css
@import "tailwindcss";

@theme {
  --color-background: hsl(220 14% 96%);
  --color-surface: hsl(0 0% 100%);
  --color-foreground: hsl(222.2 84% 4.9%);

  --color-primary: hsl(242 83% 66%);
  --color-primary-foreground: hsl(210 40% 98%);

  --color-success: hsl(142 71% 45%);
  --color-success-foreground: hsl(210 40% 98%);

  --color-warning: hsl(38 92% 50%);
  --color-warning-foreground: hsl(48 96% 89%);

  --color-danger: hsl(0 84.2% 60.2%);
  --color-danger-foreground: hsl(210 40% 98%);

  --color-border: hsl(214.3 31.8% 91.4%);
  --color-input: hsl(214.3 31.8% 91.4%);
  --color-ring: hsl(221.2 83.2% 53.3%);

  --radius-lg: 0.5rem;
  --radius-md: calc(0.5rem - 2px);
  --radius-sm: calc(0.5rem - 4px);
}

@layer base {
  * {
    border-color: var(--color-border);
  }
  body {
    background-color: var(--color-background);
    color: var(--color-foreground);
    font-family: "Inter", sans-serif;
    -webkit-font-smoothing: antialiased;
  }
}
```

## Styling Best Practices & Conventions

### 1. Always Use Tailwind Utility Classes

Never use React inline style attributes for static layout or appearance styling.

```tsx
// ✅ Correct
<div className="p-6 bg-surface rounded-lg shadow-sm border border-border">
  <h2 className="text-xl font-bold text-foreground">Client Ledger</h2>
</div>

// ❌ Incorrect (Prohibited)
<div style={{ padding: '24px', backgroundColor: '#ffffff', borderRadius: '8px' }}>
  <h2 style={{ fontSize: '20px', fontWeight: 700 }}>Client Ledger</h2>
</div>
```

### 2. Using the `cn()` Utility for Conditional Styling

When combining static utility classes with dynamic JavaScript conditional classes, use the `cn()` helper function (which encapsulates `clsx` and `twMerge` to prevent class collision).

```tsx
import React from 'react';
import { cn } from '@/lib/utils';

interface IndicatorProps {
  isActive: boolean;
  className?: string;
}

export function StatusIndicator({ isActive, className }: IndicatorProps) {
  return (
    <span className={cn(
      "inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold",
      isActive ? "bg-success text-success-foreground" : "bg-danger text-danger-foreground",
      className
    )}>
      {isActive ? 'Active' : 'Suspended'}
    </span>
  );
}
```

### 3. Responsive Layout Grid Patterns

Always adhere to standard mobile-first breakpoint design patterns. Breakpoint scale:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

```tsx
// Responsive grid: 1 column on mobile, 2 on tablet, 3 on desktop
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <Card />
  <Card />
  <Card />
</div>
```

## Dark Mode Policy

**Light Mode Exclusive:** The platform is currently optimized strictly for light mode aesthetics to ensure maximum readability in enterprise accounting environments. Do not write or maintain `dark:` prefixed utility classes in component code.

## CSS Animations & Micro-Interactions

For simple state transitions (e.g., hover states, button scaling), rely on Tailwind's native transition utilities:

```tsx
<button className="bg-primary text-primary-foreground px-4 py-2 rounded-lg transition-all duration-200 hover:bg-primary/90 hover:shadow-md active:scale-95">
  Save Record
</button>
```

For complex UI animations (like expanding accordions, modal spring appearances, and page slide-ins), utilize **Framer Motion**:

```tsx
import React from 'react';
import { motion } from 'framer-motion';

<motion.div
  initial={{ opacity: 0, y: 10 }}
  animate={{ opacity: 1, y: 0 }}
  exit={{ opacity: 0, y: -10 }}
  transition={{ duration: 0.2 }}
>
  {modalContent}
</motion.div>
```
