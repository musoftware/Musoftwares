import { forwardRef, InputHTMLAttributes, ReactNode, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type FieldProps = InputHTMLAttributes<HTMLInputElement> & {
    id: string;
    label: string;
    error?: string;
    hint?: ReactNode;
    labelAction?: ReactNode;
    trailing?: ReactNode;
};

/**
 * One input for every guest page. Four states, no heavy borders:
 * empty is a light gray fill, focus adds a thin dark border, an error turns the fill light red
 * and shows one short line under the field.
 */
export const AuthField = forwardRef<HTMLInputElement, FieldProps>(
    ({ id, label, error, hint, labelAction, trailing, className, ...input }, ref) => (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-3">
                <label htmlFor={id} className="text-[13px] font-medium text-[#111] dark:text-zinc-100">{label}</label>
                {labelAction}
            </div>
            <div className="relative">
                <input
                    ref={ref}
                    id={id}
                    name={input.name ?? id}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'h-12 w-full rounded-xl border-[1.5px] border-transparent bg-[#F2F2F2] px-4 text-[15px] text-[#111] outline-none',
                        'placeholder:text-[#999] transition-[background-color,border-color] duration-150',
                        'focus:border-[#111] focus:bg-white focus:outline-none focus:ring-0 focus-visible:outline-none',
                        'dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-white dark:focus:bg-zinc-900',
                        error && 'bg-[#FDECEC] focus:border-[#D93025] dark:bg-red-950/40',
                        trailing && 'pe-12',
                        className,
                    )}
                    {...input}
                />
                {trailing}
            </div>
            {error && <p id={`${id}-error`} className="calm-fade text-[13px] text-[#D93025]">{error}</p>}
            {!error && hint}
        </div>
    ),
);
AuthField.displayName = 'AuthField';

type PasswordProps = Omit<FieldProps, 'type' | 'trailing'> & { canReveal?: boolean };

/** Password input with a show/hide eye button. */
export function PasswordField({ canReveal = true, ...props }: PasswordProps) {
    const [visible, setVisible] = useState(false);
    const toggle = canReveal ? (
        <button
            type="button"
            onClick={() => setVisible((v) => !v)}
            className="absolute inset-y-0 end-0 flex w-12 items-center justify-center text-[#555] hover:text-[#111] dark:text-zinc-400 dark:hover:text-white"
            aria-label={visible ? __('general.hide_password') : __('general.show_password')}
        >
            {visible ? <EyeOff className="h-[18px] w-[18px]" /> : <Eye className="h-[18px] w-[18px]" />}
        </button>
    ) : undefined;

    return <AuthField {...props} type={visible ? 'text' : 'password'} trailing={toggle} />;
}
