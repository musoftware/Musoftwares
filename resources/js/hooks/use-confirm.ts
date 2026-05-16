import { useCallback, useState } from 'react';

export function useConfirm() {
    const [isOpen, setIsOpen] = useState(false);
    const [confirmConfig, setConfirmConfig] = useState<unknown>(null);

    const confirm = useCallback((config: unknown) => {
        setConfirmConfig(config);
        setIsOpen(true);
    }, []);

    const close = useCallback(() => {
        setIsOpen(false);
        setConfirmConfig(null);
    }, []);

    return { confirm, isOpen, confirmConfig, close };
}
