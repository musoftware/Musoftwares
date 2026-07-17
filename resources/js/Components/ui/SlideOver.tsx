import React, { useCallback, useEffect, useState } from 'react';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { cn } from '@/lib/utils';

export interface SlideOverProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: React.ReactNode;
    description?: React.ReactNode;
    children?: React.ReactNode;
    className?: string;
    side?: 'right' | 'left';
    deepLinkParam?: string;
    deepLinkId?: string | number | null;
}

export function SlideOver({
    open,
    onOpenChange,
    title,
    description,
    children,
    className,
    side = 'right',
    deepLinkParam,
    deepLinkId,
}: SlideOverProps) {
    const clearQuery = useCallback(() => {
        if (typeof window === 'undefined') return;
        if (!deepLinkParam) return;
        const url = new URL(window.location.href);
        if (!url.searchParams.has(deepLinkParam)) return;
        url.searchParams.delete(deepLinkParam);
        const newPath = url.pathname + (url.search ? url.search : '') + url.hash;
        window.history.replaceState({}, '', newPath);
    }, [deepLinkParam]);

    const handleOpenChange = useCallback(
        (next: boolean) => {
            if (!next) {
                clearQuery();
            }
            onOpenChange(next);
        },
        [clearQuery, onOpenChange],
    );

    return (
        <Sheet open={open} onOpenChange={handleOpenChange}>
            <SheetContent
                side={side}
                className={cn('w-full gap-0 overflow-y-auto p-0 sm:max-w-md', className)}
            >
                {(title || description) && (
                    <SheetHeader className="border-b border-slate-100 bg-slate-50/50 p-6 text-start">
                        {title && <SheetTitle className="text-lg font-semibold text-slate-900">{title}</SheetTitle>}
                        {description && (
                            <SheetDescription className="mt-1 text-sm text-slate-500">{description}</SheetDescription>
                        )}
                    </SheetHeader>
                )}
                <div className="p-6">{children}</div>
            </SheetContent>
        </Sheet>
    );
}

export interface UseSlideOverDeepLinkOptions {
    param: string;
    onOpen: (id: string) => void;
}

export function useSlideOverDeepLink({ param, onOpen }: UseSlideOverDeepLinkOptions) {
    const [openId, setOpenId] = useState<string | null>(null);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const id = new URL(window.location.href).searchParams.get(param);
        if (id) {
            setOpenId(id);
            onOpen(id);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [param]);

    const close = useCallback(() => setOpenId(null), []);
    const open = useCallback((id: string | number) => {
        const value = String(id);
        setOpenId(value);
        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.set(param, value);
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    }, [param]);

    return { openId, close, open } as const;
}

export default SlideOver;
