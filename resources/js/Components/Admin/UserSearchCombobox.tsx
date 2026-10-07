import React from 'react';
import { PremiumCombobox, Option } from '@/Components/ui/PremiumCombobox';
import { __ } from '@/lib/i18n';

interface UserSearchComboboxProps {
    value: string | number | null;
    onChange: (value: string | number | null, option?: Option) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
    /** Label for an "all users" / "none" choice with an empty value. Omit to hide it. */
    emptyOptionLabel?: string;
}

/**
 * Searchable user picker backed by the admin users search endpoint.
 * Loads at most a small page of matches per keystroke instead of the whole users table,
 * and resolves the label of a preselected user by id for edit forms and filters.
 */
export function UserSearchCombobox({
    value,
    onChange,
    placeholder,
    searchPlaceholder,
    emptyText,
    className,
    emptyOptionLabel,
}: UserSearchComboboxProps) {
    const options = emptyOptionLabel ? [{ value: '', label: emptyOptionLabel }] : [];

    return (
        <PremiumCombobox
            value={value}
            onChange={onChange}
            options={options}
            asyncEndpoint={route('admin.users.search', { include_admins: 1 })}
            searchParam="q"
            selectedParam="id"
            placeholder={placeholder ?? __('general.select_user')}
            searchPlaceholder={searchPlaceholder ?? __('general.search_users')}
            emptyText={emptyText}
            className={className}
        />
    );
}
