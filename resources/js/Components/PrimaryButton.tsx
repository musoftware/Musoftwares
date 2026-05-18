/**
 * PrimaryButton — compatibility shim.
 * Delegates to the shadcn/ui Button (default variant).
 * All existing usage remains identical; just swap the import to
 * `import { Button } from '@/Components/ui/button'` when you refactor each page.
 */
import { Button } from '@/Components/ui/button';
import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <Button
            {...props}
            className={className}
            disabled={disabled}
        >
            {children}
        </Button>
    );
}
