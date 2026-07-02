import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

type FlashBag = {
    message?: string;
    success?: string;
    error?: string;
    danger?: string;
    warning?: string;
    info?: string;
    [key: string]: unknown;
};

export function useInertiaNotifications() {
    const { flash, errors } = usePage().props as { flash?: FlashBag; errors?: Record<string, string> };
    const { toast } = useToast();

    const lastToastRef = useRef<{
        message?: string;
        success?: string;
        error?: string;
        danger?: string;
        warning?: string;
        info?: string;
        errorsSerialized?: string;
    }>({});

    useEffect(() => {
        if (flash?.message && lastToastRef.current.message !== flash.message) {
            toast({ description: flash.message });
            lastToastRef.current.message = flash.message;
        }
        if (flash?.success && lastToastRef.current.success !== flash.success) {
            toast({ description: flash.success });
            lastToastRef.current.success = flash.success;
        }
        if (flash?.error && lastToastRef.current.error !== flash.error) {
            toast({ description: flash.error, variant: 'destructive' });
            lastToastRef.current.error = flash.error;
        }
        if (flash?.danger && lastToastRef.current.danger !== flash.danger) {
            toast({ description: flash.danger, variant: 'destructive' });
            lastToastRef.current.danger = flash.danger;
        }
        if (flash?.warning && lastToastRef.current.warning !== flash.warning) {
            toast({ description: flash.warning });
            lastToastRef.current.warning = flash.warning;
        }
        if (flash?.info && lastToastRef.current.info !== flash.info) {
            toast({ description: flash.info });
            lastToastRef.current.info = flash.info;
        }

        if (!flash?.message) lastToastRef.current.message = undefined;
        if (!flash?.success) lastToastRef.current.success = undefined;
        if (!flash?.error) lastToastRef.current.error = undefined;
        if (!flash?.danger) lastToastRef.current.danger = undefined;
        if (!flash?.warning) lastToastRef.current.warning = undefined;
        if (!flash?.info) lastToastRef.current.info = undefined;
    }, [flash, toast]);

    useEffect(() => {
        const errorsSerialized = JSON.stringify(errors || {});
        if (errors && Object.keys(errors).length > 0) {
            if (lastToastRef.current.errorsSerialized !== errorsSerialized) {
                if (errors.error) {
                    toast({
                        title: __('general.system_error'),
                        description: errors.error,
                        variant: 'destructive',
                    });
                } else {
                    const firstVal = Object.values(errors)[0];
                    toast({
                        title: __('general.please_fix_the_following'),
                        description: firstVal as string,
                        variant: 'destructive',
                    });
                }
                lastToastRef.current.errorsSerialized = errorsSerialized;
            }
        } else {
            lastToastRef.current.errorsSerialized = errorsSerialized;
        }
    }, [errors, toast]);
}