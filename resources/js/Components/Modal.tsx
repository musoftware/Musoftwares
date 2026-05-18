import { PropsWithChildren } from 'react';
import {
    Dialog,
    DialogContent,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';

const maxWidthClasses = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
} as const;

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}: PropsWithChildren<{
    show: boolean;
    maxWidth?: keyof typeof maxWidthClasses;
    closeable?: boolean;
    onClose: CallableFunction;
}>) {
    return (
        <Dialog
            open={show}
            onOpenChange={(open) => {
                if (!open && closeable) onClose();
            }}
        >
            <DialogContent
                showCloseButton={closeable}
                className={cn(
                    'w-full max-w-none rounded-xl bg-white p-0 shadow-xl ring-1 ring-slate-200',
                    maxWidthClasses[maxWidth],
                )}
            >
                {children}
            </DialogContent>
        </Dialog>
    );
}
