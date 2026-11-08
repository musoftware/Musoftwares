import React from 'react';
import { Label } from '@/Components/ui/label';

interface Props {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}

export default function ShopField({ id, label, hint, error, children }: Props) {
    return (
        <div className="space-y-1.5 min-w-0">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-sm text-muted-foreground">{hint}</p>}
            {error && <p className="text-sm text-red-700" role="alert">{error}</p>}
        </div>
    );
}
