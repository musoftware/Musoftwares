---
name: Admin UI Color Rules
description: Defines the strict color palette for Admin interfaces, enforcing a black and white aesthetic.
---

# Admin UI Color Rules

## 1. The "Black & White Only" Aesthetic

The Musoftware Admin interface strictly adheres to a **Premium Black & White** design system. 

**Rule:**
- **No decorative colors:** Never use colors like `text-emerald-500`, `text-blue-500`, or `text-rose-500` for general icons, buttons, or links.
- **Default text/icons:** Use `text-slate-800`, `text-slate-900`, or `text-black` for all standard UI elements.
- **Hover states:** Hover states should manipulate opacity, switch to black, or use subtle grays (e.g., `hover:bg-slate-50 hover:text-black`), rather than introducing brand colors.

## 2. When to Use Colors (Semantic Alerts)

Colors are strictly reserved for **Semantic Alerts** and **Status Indicators** to quickly convey state or urgency to the Admin.

**Allowed Semantic Colors:**
- **Success/Verified:** Green (e.g., `text-green-600` for KYC Verified or Active Status)
- **Warning/Pending:** Yellow/Amber (e.g., `text-amber-500` for Pending Review)
- **Error/Danger:** Red (e.g., `text-red-600` for Blocked, Failed, or Destructive Actions)
- **Info/System:** Blue/Indigo (e.g., `text-blue-600` for new items this week)

### Implementation Example

```jsx
// ❌ INCORRECT (Decorative colors are forbidden)
<Button variant="outline">
    <Wallet className="h-4 w-4 text-emerald-500" /> 
    <span>Finance & Billing</span>
</Button>

// ✅ CORRECT (Strict black and white)
<Button variant="outline" className="hover:text-black">
    <Wallet className="h-4 w-4 text-slate-800" /> 
    <span className="text-slate-700">Finance & Billing</span>
</Button>

// ✅ CORRECT (Semantic status indicator)
<div className="text-red-600 font-bold">Blocked Account</div>
```
