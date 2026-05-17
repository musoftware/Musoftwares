# Frontend State Management Architecture

## Architectural Philosophy

The ERP System enforces a **Minimalist State Management Policy**. Because Inertia.js synchronizes server-side data directly with React components via page props, heavy client-side state stores (such as Redux or Zustand) are strictly unnecessary and prohibited. Adding external state stores introduces data synchronization latency between client and database.

State across the frontend is managed via four distinct layers:
1. **Server State / Page Data:** Provided by Inertia.js props from Laravel controllers.
2. **Form State & Mutations:** Encapsulated within `@inertiajs/react`'s `useForm` hook.
3. **Local Component UI State:** Managed via standard React hooks (`useState`, `useReducer`).
4. **Global Layout & Context State:** Provided by lightweight React Context providers and Inertia page sharing.

## Form State Management (`useForm`)

The `useForm` hook manages input synchronization, submission processing flags, progress bars for file uploads, and automatic validation error binding in TypeScript.

```tsx
import { useForm } from '@inertiajs/react';

interface InvoiceForm {
  invoice_number: string;
  client_id: number | string;
  items: Array<{ id: number; title: string; total: number }>;
  amount: number;
}

const { 
  data, 
  setData, 
  post, 
  put, 
  delete: destroy, 
  processing, 
  errors, 
  reset, 
  clearErrors 
} = useForm<InvoiceForm>({
  invoice_number: 'INV-2024-0001',
  client_id: '',
  items: [],
  amount: 0.00
});

// Mutating single fields
setData('amount', 1500.00);

// Mutating nested state structures
setData('items', data.items.map(item => item.id === 1 ? { ...item, total: 50.00 } : item));
```

### Handling Async Form Submissions

```tsx
const handleSave = (e: React.FormEvent) => {
  e.preventDefault();
  
  post('/erp/invoices', {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      console.log('Invoice successfully committed to database');
      reset('items', 'amount');
    },
    onError: (errors) => {
      console.error('Validation errors encountered:', errors);
    }
  });
};
```

## Global Shared Context (`usePage`)

Data injected by Laravel's `HandleInertiaRequests` middleware is accessible in any React component via `usePage().props`.

```tsx
import { usePage } from '@inertiajs/react';

interface SharedProps {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      role: string;
    };
  };
  settings: Record<string, string>;
}

export function useAuth() {
  const { auth } = usePage<SharedProps>().props;
  return auth;
}

export function useSettings() {
  const { settings } = usePage<SharedProps>().props;
  return settings;
}
```

## Toast & Notification State (`useToast`)

System feedback and flash notifications are managed via Radix UI Toast / shadcn primitives.

```tsx
import { useToast } from '@/Components/ui';

export function ActionTrigger() {
  const { toast } = useToast();

  const handleClick = () => {
    toast({
      title: "Success",
      description: "Escrow deposit secured successfully.",
      variant: "default",
    });
  };
}
```

## Creating Domain Custom Hooks

When component state logic becomes complex (such as managing invoice line item calculations or filtering logic), extract it into a dedicated custom hook inside `resources/js/hooks/`.

```tsx
import { useState, useMemo, useCallback } from 'react';

interface LineItem {
  id: number;
  unit_price: number;
  quantity: number;
  total: number;
}

export function useInvoiceCalculator(initialItems: LineItem[] = [], taxRate = 0.00, discountAmount = 0.00) {
  const [items, setItems] = useState<LineItem[]>(initialItems);

  const addItem = useCallback((newItem: Omit<LineItem, 'total'>) => {
    setItems(prev => [...prev, { ...newItem, total: newItem.unit_price * newItem.quantity }]);
  }, []);

  const removeItem = useCallback((id: number) => {
    setItems(prev => prev.filter(item => item.id !== id));
  }, []);

  const updateQuantity = useCallback((id: number, newQuantity: number) => {
    setItems(prev => prev.map(item => item.id === id ? {
      ...item,
      quantity: newQuantity,
      total: item.unit_price * newQuantity
    } : item));
  }, []);

  const subtotal = useMemo(() => {
    return items.reduce((sum, item) => sum + item.total, 0);
  }, [items]);

  const taxAmount = useMemo(() => {
    return (subtotal - discountAmount) * (taxRate / 100);
  }, [subtotal, discountAmount, taxRate]);

  const grandTotal = useMemo(() => {
    return Math.max(0, subtotal - discountAmount + taxAmount);
  }, [subtotal, discountAmount, taxAmount]);

  return {
    items,
    addItem,
    removeItem,
    updateQuantity,
    subtotal,
    taxAmount,
    grandTotal
  };
}
```

## Optimizing React Performance & Preventing Re-Renders

1. **Use `useMemo` for Expensive Computations:** Always wrap complex sorting, financial aggregation, or filtering routines in `useMemo` to prevent recalculation on every UI render.
2. **Use `useCallback` for Event Handlers:** When passing callback functions to child components (like table row actions or modal triggers), wrap them in `useCallback` to preserve reference equality.
3. **React DevTools:** Use the React Developer Tools browser extension to inspect component hierarchies and ensure props are not changing unintentionally.
