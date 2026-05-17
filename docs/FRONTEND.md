# Frontend Development Guide

## Overview

The ERP System frontend is a **Single Page Application (SPA)** built with **React 18** and strictly typed **TypeScript (`.tsx`)**, powered by **Inertia.js 2.0**. This architecture allows us to build modern React interfaces without maintaining separate client-side routing, API authentication tokens, or complex state synchronization layers.

## Core Layouts

The application provides six primary layouts located in `resources/js/Layouts/`:

```text
resources/js/Layouts/
├── AdminLayout.tsx         ← Dedicated layout for super-admin dashboard and moderation panels
├── AuthenticatedLayout.tsx ← General layout for authenticated users
├── ClientLayout.tsx        ← Layout optimized for ERP tenant accounting clients
├── GuestLayout.tsx         ← Minimal layout for login, registration, and password resets
├── MarketplaceLayout.tsx   ← Layout for service and freelance marketplace exploration
└── PublicLayout.tsx        ← Public-facing marketing and landing page layout
```

### Example Usage in Pages

```tsx
import React from 'react';
import ClientLayout from '@/Layouts/ClientLayout';
import { Head } from '@inertiajs/react';

interface Props {
  auth: { user: { name: string; email: string } };
  title: string;
}

export default function Dashboard({ auth, title }: Props) {
  return (
    <ClientLayout user={auth.user}>
      <Head title={title} />
      <div className="p-6">
        <h1 className="text-2xl font-bold">Welcome, {auth.user.name}</h1>
      </div>
    </ClientLayout>
  );
}
```

## Inertia.js 2.0 Page Navigation

When navigating between pages in React, never use standard HTML `<a>` tags (which force full browser reloads). Instead, always use the Inertia `<Link>` component.

```tsx
import { Link } from '@inertiajs/react';

// Standard SPA navigation
<Link href="/erp/invoices" className="text-primary hover:underline">
  View All Invoices
</Link>

// Navigation with HTTP POST method
<Link href="/logout" method="post" as="button" className="text-danger">
  Sign Out
</Link>
```

## Form Handling (`useForm`)

Form validation and submission are managed via `@inertiajs/react`'s `useForm` hook, providing automatic loading states, file upload progress bars, and server validation error binding.

```tsx
import React, { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { PrimaryButton, TextInput, InputLabel, InputError } from '@/Components/ui';

interface FormValues {
  title: string;
  budget: number;
  currency_code: string;
}

export default function PostJobForm() {
  const { data, setData, post, processing, errors, reset } = useForm<FormValues>({
    title: '',
    budget: 500,
    currency_code: 'USD',
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    post('/freelance/jobs', {
      onSuccess: () => reset(),
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <InputLabel htmlFor="title" value="Job Title" />
        <TextInput
          id="title"
          value={data.title}
          onChange={(e) => setData('title', e.target.value)}
          required
        />
        <InputError message={errors.title} className="mt-2" />
      </div>
      
      <PrimaryButton disabled={processing}>
        {processing ? 'Publishing...' : 'Publish Job'}
      </PrimaryButton>
    </form>
  );
}
```

## Global Shared Data (`usePage`)

Data attached globally by Laravel's `HandleInertiaRequests` middleware is accessible in any component via `usePage`.

```tsx
import { usePage } from '@inertiajs/react';

interface PageProps {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      role: string;
    };
  };
  flash: {
    success?: string;
    error?: string;
  };
}

export function useAuthUser() {
  const { auth } = usePage<PageProps>().props;
  return auth.user;
}
```

## Development & Asset Compilation

During local development, run the Vite development server for instantaneous Hot Module Replacement (HMR).

```bash
# Start Vite HMR server
npm run dev

# Run TypeScript compiler checks and build production assets
npm run build
```
