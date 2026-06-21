import React, { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { AlertTriangle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';

// ── Simple confirm modal (yes/no) ────────────────────────────────────────────

export interface ConfirmModalProps {
    isOpen: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'danger' | 'default';
    onConfirm: () => void;
    onCancel: () => void;
    loading?: boolean;
}

export function ConfirmModal({
    isOpen,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    variant = 'default',
    onConfirm,
    onCancel,
    loading = false,
}: ConfirmModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onCancel()}>
            <DialogContent className="sm:max-w-[420px]">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        {variant === 'danger' ? (
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 border border-red-100">
                                <AlertTriangle className="h-4 w-4 text-red-600" />
                            </div>
                        ) : (
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
                                <Info className="h-4 w-4 text-slate-600" />
                            </div>
                        )}
                        <DialogTitle className="text-base font-semibold">{title}</DialogTitle>
                    </div>
                    {description && (
                        <DialogDescription className="mt-2 ms-12 text-sm text-slate-500 leading-relaxed">
                            {description}
                        </DialogDescription>
                    )}
                </DialogHeader>
                <DialogFooter className="mt-2 flex gap-2 justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onCancel}
                        disabled={loading}
                        className="shadow-none border-slate-200"
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        size="sm"
                        onClick={onConfirm}
                        disabled={loading}
                        className={cn(
                            'shadow-none',
                            variant === 'danger'
                                ? 'bg-red-600 hover:bg-red-700 text-white border-red-600'
                                : ''
                        )}
                    >
                        {loading ? 'Processing...' : confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// ── Prompt modal (collect a value + confirm) ─────────────────────────────────

export interface PromptModalProps {
    isOpen: boolean;
    title: string;
    description?: string;
    label?: string;
    placeholder?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    inputType?: 'text' | 'number' | 'email';
    onConfirm: (value: string) => void;
    onCancel: () => void;
    loading?: boolean;
}

export function PromptModal({
    isOpen,
    title,
    description,
    label = 'Value',
    placeholder = '',
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    inputType = 'text',
    onConfirm,
    onCancel,
    loading = false,
}: PromptModalProps) {
    const [value, setValue] = useState('');

    const handleConfirm = () => {
        if (!value.trim()) return;
        onConfirm(value);
        setValue('');
    };

    const handleCancel = () => {
        setValue('');
        onCancel();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleCancel()}>
            <DialogContent className="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle className="text-base font-semibold">{title}</DialogTitle>
                    {description && (
                        <DialogDescription className="text-sm text-slate-500 mt-1">
                            {description}
                        </DialogDescription>
                    )}
                </DialogHeader>
                <div className="mt-2 space-y-2">
                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        {label}
                    </Label>
                    <Input
                        type={inputType}
                        placeholder={placeholder}
                        value={value}
                        onChange={(e) => setValue(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleConfirm()}
                        className="h-10"
                        autoFocus
                    />
                </div>
                <DialogFooter className="mt-4 flex gap-2 justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={handleCancel}
                        disabled={loading}
                        className="shadow-none border-slate-200"
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        size="sm"
                        onClick={handleConfirm}
                        disabled={loading || !value.trim()}
                        className="shadow-none"
                    >
                        {loading ? 'Processing...' : confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
