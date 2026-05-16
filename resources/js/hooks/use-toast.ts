import { useCallback, useState } from 'react';

// Basic wrapper for toast, assuming a radox/shadcn toast setup
export function useToast() {
    const [toasts, setToasts] = useState<unknown[]>([]);

    const toast = useCallback(
        ({
            title,
            description,
            variant = 'default',
        }: {
            title: string;
            description?: string;
            variant?: string;
        }) => {
            setToasts((prev) => [
                ...prev,
                { id: Date.now(), title, description, variant },
            ]);
        },
        [],
    );

    return { toast, toasts };
}
