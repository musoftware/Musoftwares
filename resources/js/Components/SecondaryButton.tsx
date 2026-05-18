/**
 * SecondaryButton — compatibility shim.
 * Delegates to shadcn/ui Button (outline variant).
 */
import { Button } from '@/Components/ui/button';
import { ButtonHTMLAttributes } from 'react';

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <Button
            {...props}
            type={type}
            variant="outline"
            className={className}
            disabled={disabled}
        >
            {children}
        </Button>
    );
}
