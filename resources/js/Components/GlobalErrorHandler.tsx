import React, { useEffect, useState } from 'react';
import { useToast } from './ui/use-toast';
import { ToastAction } from './ui/toast';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './ui/dialog';
import { Button } from './ui/button';

export function GlobalErrorHandler() {
    const { toast } = useToast();
    const [sessionExpired, setSessionExpired] = useState(false);

    useEffect(() => {
        const handleNetworkError = () => {
            toast({
                title: "Connection error",
                description: "Please check your internet and try again.",
                variant: "destructive",
                action: <ToastAction altText="Retry" onClick={() => window.location.reload()}>Retry</ToastAction>
            });
        };

        const handleServerError = () => {
            toast({
                title: "Something went wrong on our end",
                description: "We're working on it. Please try again in a moment.",
                variant: "destructive",
            });
        };

        const handleSessionExpired = () => {
            setSessionExpired(true);
        };

        const handleLongRequest = () => {
            toast({
                title: "This is taking longer than usual...",
                description: "Please wait or cancel the request.",
                action: <ToastAction altText="Cancel" onClick={() => window.stop()}>Cancel</ToastAction>
            });
        };

        window.addEventListener('app:network-error', handleNetworkError);
        window.addEventListener('app:server-error', handleServerError);
        window.addEventListener('app:session-expired', handleSessionExpired);
        window.addEventListener('app:long-request', handleLongRequest);

        return () => {
            window.removeEventListener('app:network-error', handleNetworkError);
            window.removeEventListener('app:server-error', handleServerError);
            window.removeEventListener('app:session-expired', handleSessionExpired);
            window.removeEventListener('app:long-request', handleLongRequest);
        };
    }, [toast]);

    const handleLoginAgain = () => {
        window.location.href = '/login';
    };

    return (
        <Dialog open={sessionExpired} onOpenChange={setSessionExpired}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle className="font-sora text-slate-900 text-xl">{__('general.your_session_has_expired')}</DialogTitle>
                    <DialogDescription className="font-dm-sans text-slate-500">{__('general.please_log_in_again_to_continue_working_your_unsaved_changes_will_be_preserved')}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button onClick={handleLoginAgain} className="bg-indigo-600 hover:bg-indigo-700 text-white font-dm-sans">{__('general.log_in_again')}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
