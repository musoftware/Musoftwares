import { cn } from '@/lib/utils';
import { Check, Loader2 } from 'lucide-react';
import React, { useEffect, useState } from 'react';

export interface LoadingButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    loading?: boolean;
    success?: boolean;
    loadingText?: string;
    successText?: string;
}

export function LoadingButton({
    loading = false,
    success = false,
    loadingText = 'Loading...',
    successText = 'Success',
    children,
    className,
    disabled,
    ...props
}: LoadingButtonProps) {
    const [showSuccess, setShowSuccess] = useState(false);

    useEffect(() => {
        if (success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 1500);
            return () => clearTimeout(timer);
        }
    }, [success]);

    const isInteractiveDisabled = loading || showSuccess || disabled;

    return (
        <button
            {...props}
            disabled={isInteractiveDisabled}
            className={cn(
                'bg-primary hover:bg-primary-hover focus:ring-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-[14px] font-medium text-white shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:outline-none active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-70',
                className,
            )}
        >
            {showSuccess ? (
                <>
                    <Check className="me-2 h-4 w-4" />
                    {successText}
                </>
            ) : loading ? (
                <>
                    <Loader2 className="me-2 h-4 w-4 animate-spin" />
                    {loadingText}
                </>
            ) : (
                children
            )}
        </button>
    );
}
