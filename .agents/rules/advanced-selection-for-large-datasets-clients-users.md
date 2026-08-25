# Rule: Advanced Searchable Selection for Users, Clients, and Large Datasets

## Problem Statement
Standard HTML `<select>` elements and basic unsearchable dropdowns provide a poor user experience, lack instant search/filtering by name or email, and degrade performance when datasets grow.

---

## Rules & Guidelines

### 1. Mandatory Use of `PremiumCombobox`
- **Strictly Prohibited**: Never use raw HTML `<select>` or standard non-searchable UI dropdowns for selecting **Users**, **Clients**, **Employees**, or **Projects** across any part of the system (forms, modals, drawers, detail sheets, and filter bars).
- **Mandatory Component**: Always import and use `PremiumCombobox` from `@/Components/ui/PremiumCombobox`.

### 2. Standard Implementation Patterns

#### A. Static Array Data (e.g., users/clients passed from Inertia props)
```tsx
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';

<div className="space-y-1.5">
    <Label>{__('general.select_user')}</Label>
    <PremiumCombobox
        value={form.data.user_id ? String(form.data.user_id) : ''}
        onChange={(val) => form.setData('user_id', val ? String(val) : '')}
        options={users.map((u: any) => ({
            value: String(u.id),
            label: `${u.name} (${u.email || ''})`
        }))}
        placeholder={__('general.select_user')}
        searchPlaceholder={__('general.search_users')}
    />
    {form.errors.user_id && <p className="text-xs text-destructive">{form.errors.user_id}</p>}
</div>
```

#### B. Asynchronous Data (Large Datasets with Search Endpoint)
```tsx
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';

<PremiumCombobox
    value={selectedId}
    onChange={(val, opt) => handleSelect(val, opt)}
    asyncEndpoint={route('admin.users.search')}
    searchParam="q"
    placeholder={__('general.select_client')}
    searchPlaceholder={__('general.search_clients')}
    debounceMs={300}
/>
```

#### C. Filter Bars & Quick Assignment Drawers
When used inside filter bars or compact headers, maintain a fixed or bounded width:
```tsx
<PremiumCombobox
    className="w-[180px] sm:w-[220px]"
    value={selectedUserFilter || ''}
    onChange={(val) => setSelectedUserFilter(val ? String(val) : '')}
    options={[
        { value: '', label: __('general.all_users') },
        ...users.map((u: any) => ({ value: String(u.id), label: `${u.name} (${u.email || ''})` }))
    ]}
    placeholder={__('general.all_users')}
    searchPlaceholder={__('general.search_users')}
/>
```

---

## 3. Summary Checklist
- [ ] Are all User/Client/Employee selectors using `PremiumCombobox`?
- [ ] Is raw `<select>` completely absent from all User/Client selection logic?
- [ ] Are options formatted with descriptive labels (e.g. `Name (email)` or `Name (role)`)?
- [ ] Are pre-selected / edit values properly cast to `String` matching `option.value`?
