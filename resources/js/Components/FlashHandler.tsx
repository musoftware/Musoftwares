import React, { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { useToast } from '@/Components/ui/use-toast';

export function FlashHandler() {
    const { flash, errors } = usePage().props as any;
    const { toast } = useToast();
    
    // Store the last processed flash and errors to prevent duplicate toast triggers on re-renders
    const lastFlashRef = useRef<any>(null);
    const lastErrorsRef = useRef<any>(null);

    // Handle session flash messages
    useEffect(() => {
        if (!flash) return;

        // Determine if this is the exact same flash message we've already toasted
        const isIdentical =
            lastFlashRef.current &&
            lastFlashRef.current.message === flash.message &&
            lastFlashRef.current.success === flash.success &&
            lastFlashRef.current.error === flash.error &&
            lastFlashRef.current.danger === flash.danger &&
            lastFlashRef.current.warning === flash.warning;

        if (isIdentical) return;

        // Keep track of what we toasted
        lastFlashRef.current = { ...flash };

        // 1. Generic message / Info alert
        if (flash.message) {
            toast({
                description: flash.message,
            });
        }

        // 2. Success alert
        if (flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        // 3. Error alert
        if (flash.error) {
            toast({
                title: "Error",
                description: flash.error,
                variant: "destructive",
            });
        }

        // 4. Danger/Emergency alert
        if (flash.danger) {
            toast({
                title: "Danger",
                description: flash.danger,
                variant: "destructive",
            });
        }

        // 5. Warning alert
        if (flash.warning) {
            toast({
                title: "Warning",
                description: flash.warning,
            });
        }
    }, [flash, toast]);

    // Handle validation errors automatically
    useEffect(() => {
        if (!errors || Object.keys(errors).length === 0) return;

        // Determine if these are the exact same errors we've already toasted
        const isIdentical =
            lastErrorsRef.current &&
            JSON.stringify(lastErrorsRef.current) === JSON.stringify(errors);

        if (isIdentical) return;

        // Keep track of what we toasted
        lastErrorsRef.current = { ...errors };

        // Display the first validation error
        const firstErrorKey = Object.keys(errors)[0];
        const firstErrorMessage = errors[firstErrorKey];
        
        if (firstErrorMessage) {
            toast({
                title: "Validation Error",
                description: firstErrorMessage,
                variant: "destructive",
            });
        }
    }, [errors, toast]);

    return null;
}
