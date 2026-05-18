/**
 * InputError — inline error message.
 * Aligned with shadcn design tokens (text-destructive).
 */
import { HTMLAttributes } from 'react';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p {...props} className={`text-sm text-destructive ${className}`}>
            {message}
        </p>
    ) : null;
}
