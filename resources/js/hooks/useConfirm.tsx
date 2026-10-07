import React, { useCallback, useRef, useState } from 'react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';

export interface ConfirmOptions {
    title?: string;
    description?: string;
    confirmLabel?: string;
    variant?: 'danger' | 'default';
}

/**
 * Promise based replacement for window.confirm().
 * Render `confirmDialog` once in the component tree, then `await confirm({...})`.
 */
export function useConfirm() {
    const [options, setOptions] = useState<ConfirmOptions | null>(null);
    const resolverRef = useRef<((accepted: boolean) => void) | null>(null);

    const confirm = useCallback((next: ConfirmOptions = {}): Promise<boolean> => {
        setOptions(next);
        return new Promise<boolean>((resolve) => {
            resolverRef.current = resolve;
        });
    }, []);

    const settle = useCallback((accepted: boolean) => {
        resolverRef.current?.(accepted);
        resolverRef.current = null;
        setOptions(null);
    }, []);

    const confirmDialog = (
        <ConfirmModal
            isOpen={options !== null}
            title={options?.title ?? __('general.are_you_sure')}
            description={options?.description}
            confirmLabel={options?.confirmLabel ?? __('general.confirm')}
            variant={options?.variant ?? 'default'}
            onConfirm={() => settle(true)}
            onCancel={() => settle(false)}
        />
    );

    return { confirm, confirmDialog };
}
