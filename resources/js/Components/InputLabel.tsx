/**
 * InputLabel — compatibility shim.
 * Delegates to shadcn/ui Label, adding support for the `value` prop
 * (used as an alternative to children in Breeze-scaffold forms).
 */
import { Label } from '@/Components/ui/label';
import { LabelHTMLAttributes } from 'react';

export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & { value?: string }) {
    return (
        <Label {...props} className={className}>
            {value ?? children}
        </Label>
    );
}
