import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { useToast } from '@/Components/ui/use-toast';

export function useInertiaNotifications() {
    const { flash, errors } = usePage().props as any;
    const { toast } = useToast();
    
    // Use refs to track what we have already toasted to avoid duplicate toasts
    const lastToastRef = useRef<{
        message?: string;
        success?: string;
        error?: string;
        danger?: string;
        warning?: string;
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
            toast({ description: flash.warning, variant: 'default' }); // could be yellow if warning variant exists, default is safe
            lastToastRef.current.warning = flash.warning;
        }

        // Reset tracking for fields that have been cleared
        if (!flash?.message) lastToastRef.current.message = undefined;
        if (!flash?.success) lastToastRef.current.success = undefined;
        if (!flash?.error) lastToastRef.current.error = undefined;
        if (!flash?.danger) lastToastRef.current.danger = undefined;
        if (!flash?.warning) lastToastRef.current.warning = undefined;
    }, [flash, toast]);

    useEffect(() => {
        const errorsSerialized = JSON.stringify(errors || {});
        if (errors && Object.keys(errors).length > 0) {
            if (lastToastRef.current.errorsSerialized !== errorsSerialized) {
                if (errors.error) {
                    toast({
                        title: 'System Error',
                        description: errors.error,
                        variant: 'destructive',
                    });
                } else {
                    const firstVal = Object.values(errors)[0];
                    toast({
                        title: 'Validation Error',
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
