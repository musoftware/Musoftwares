import * as React from 'react';
import { X } from 'lucide-react';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export type SlideOverSize = 'sm' | 'md' | 'lg' | 'xl';

const SIZE_CLASSES: Record<SlideOverSize, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-2xl',
};

export interface SlideOverProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: React.ReactNode;
    description?: React.ReactNode;
    children?: React.ReactNode;
    side?: 'left' | 'right';
    size?: SlideOverSize;
    className?: string;
    contentClassName?: string;
    closeLabel?: string;
    onAfterClose?: () => void;
}

export function SlideOver({
    open,
    onOpenChange,
    title,
    description,
    children,
    side = 'right',
    size = 'md',
    className,
    contentClassName,
    closeLabel,
    onAfterClose,
}: SlideOverProps) {
    const handleOpenChange = React.useCallback(
        (next: boolean) => {
            onOpenChange(next);
            if (!next) {
                onAfterClose?.();
            }
        },
        [onOpenChange, onAfterClose],
    );

    return (
        <Sheet open={open} onOpenChange={handleOpenChange}>
            <SheetContent
                side={side}
                className={cn(
                    'flex h-full flex-col gap-0 overflow-y-auto p-0',
                    SIZE_CLASSES[size],
                    className,
                )}
            >
                {(title || description) && (
                    <SheetHeader className="border-b border-slate-100 bg-slate-50/60 px-6 py-5 text-start">
                        {title && (
                            <SheetTitle className="text-lg font-semibold text-slate-900">
                                {title}
                            </SheetTitle>
                        )}
                        {description && (
                            <SheetDescription className="mt-1 text-sm text-slate-500">
                                {description}
                            </SheetDescription>
                        )}
                    </SheetHeader>
                )}
                <div className={cn('flex-1 overflow-y-auto p-6', contentClassName)}>
                    {children}
                </div>
                <button
                    type="button"
                    aria-label={closeLabel ?? __('general.close')}
                    className="absolute end-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    onClick={() => handleOpenChange(false)}
                >
                    <X className="h-4 w-4" />
                </button>
            </SheetContent>
        </Sheet>
    );
}

export default SlideOver;
