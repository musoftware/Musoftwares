import {
    Dialog,
    DialogContent,
    DialogOverlay,
    DialogPortal,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { AlertCircle, AlertTriangle, HelpCircle, Loader2 } from 'lucide-react';

export function ConfirmDialog({
    open,
    onClose,
    onConfirm,
    title,
    description,
    variant = 'default',
    confirmLabel = 'Confirm',
    loading = false,
    details,
}) {
    const variants = {
        danger: {
            icon: AlertTriangle,
            iconBg: 'bg-danger-light',
            iconColor: 'text-danger',
            btnBg: 'bg-danger hover:bg-red-600 focus:ring-danger',
        },
        warning: {
            icon: AlertCircle,
            iconBg: 'bg-warning-light',
            iconColor: 'text-warning',
            btnBg: 'bg-warning hover:bg-amber-600 focus:ring-warning',
        },
        default: {
            icon: HelpCircle,
            iconBg: 'bg-primary-light',
            iconColor: 'text-primary',
            btnBg: 'bg-primary hover:bg-primary-hover focus:ring-primary',
        },
    };

    const v = variants[variant] || variants.default;
    const Icon = v.icon;

    return (
        <Dialog
            open={open}
            onOpenChange={(isOpen) => !loading && !isOpen && onClose()}
        >
            <DialogPortal>
                <DialogOverlay className="bg-text-primary/20 z-50 backdrop-blur-sm transition-all duration-200" />
                <DialogContent className="bg-surface border-border fixed top-[50%] start-[50%] z-50 flex w-full max-w-[400px] translate-x-[-50%] translate-y-[-50%] flex-col items-center rounded-xl border p-6 text-center shadow-lg outline-none">
                    <div
                        className={cn(
                            'mb-4 flex h-12 w-12 items-center justify-center rounded-full',
                            v.iconBg,
                        )}
                    >
                        <Icon className={cn('h-6 w-6', v.iconColor)} />
                    </div>

                    <h2 className="font-sora text-text-primary mb-2 text-[16px] font-semibold">
                        {title}
                    </h2>

                    {description && (
                        <p className="text-text-secondary mb-6 font-sans text-[14px]">
                            {description}
                        </p>
                    )}

                    {details && (
                        <div className="bg-surface-raised border-border text-text-primary mb-6 w-full rounded-lg border p-3 text-start text-[13px] font-medium break-words">
                            {details}
                        </div>
                    )}

                    <div className="mt-2 flex w-full flex-col-reverse gap-3 sm:flex-row sm:gap-4">
                        <button
                            type="button"
                            disabled={loading}
                            onClick={onClose}
                            className="bg-surface text-text-primary border-border hover:bg-surface-raised flex-1 rounded-lg border px-4 py-2 text-[14px] font-medium transition-colors disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            disabled={loading}
                            onClick={onConfirm}
                            className={cn(
                                'flex flex-1 items-center justify-center rounded-lg px-4 py-2 text-[14px] font-medium text-white transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:opacity-70',
                                v.btnBg,
                            )}
                        >
                            {loading && (
                                <Loader2 className="me-2 h-4 w-4 animate-spin" />
                            )}
                            {loading ? 'Processing...' : confirmLabel}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </Dialog>
    );
}
