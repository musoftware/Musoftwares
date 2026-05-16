import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export function useUnsavedChangesWarning(isDirty: boolean) {
    useEffect(() => {
        // Handle Inertia navigations
        const removeListener = router.on('before', (event) => {
            if (isDirty) {
                if (!window.confirm('You have unsaved changes. Leave anyway?')) {
                    event.preventDefault();
                }
            }
        });

        // Handle external navigations / tab closures
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            removeListener();
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [isDirty]);
}
