import { Input as InputPrimitive } from '@base-ui/react/input';
import * as React from 'react';
import { useEffect, useRef } from 'react';

import { cn } from '@/lib/utils';

export interface InputProps extends React.ComponentProps<'input'> {
  error?: string | boolean;
  helperText?: string;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, error, helperText, ...props }, forwardedRef) => {
    const internalRef = useRef<HTMLInputElement>(null);

    // Merge forwardedRef and internalRef
    const setRefs = React.useCallback(
      (node: HTMLInputElement | null) => {
        (internalRef as React.MutableRefObject<HTMLInputElement | null>).current = node;
        if (typeof forwardedRef === 'function') {
          forwardedRef(node);
        } else if (forwardedRef) {
          (forwardedRef as React.MutableRefObject<HTMLInputElement | null>).current = node;
        }
      },
      [forwardedRef]
    );

    // Auto-focus on first error
    useEffect(() => {
      if (error && internalRef.current) {
        // We use a small timeout to ensure the DOM has updated and we can find the first invalid element
        const timeout = setTimeout(() => {
          const firstInvalid = document.querySelector('[aria-invalid="true"]') as HTMLElement;
          if (firstInvalid === internalRef.current) {
            internalRef.current.focus();
          }
        }, 50);
        return () => clearTimeout(timeout);
      }
    }, [error]);

    const hasError = !!error;
    const errorText = typeof error === 'string' ? error : helperText;

    return (
      <div className="w-full">
        <InputPrimitive
          ref={setRefs}
          type={type}
          data-slot="input"
          aria-invalid={hasError ? "true" : "false"}
          className={cn(
            'border-input file:text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:bg-input/50 aria-invalid:border-red-500 aria-invalid:ring-3 aria-invalid:ring-red-500/20 dark:bg-input/30 dark:disabled:bg-input/80 dark:aria-invalid:border-red-500/50 dark:aria-invalid:ring-red-500/40 h-8 w-full min-w-0 rounded-lg border bg-transparent px-2.5 py-1 text-base outline-none transition-colors file:inline-flex file:h-6 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
            hasError && 'border-red-500',
            className,
          )}
          {...props}
        />
        {hasError && errorText && (
          <p className="font-dm-sans mt-1 text-sm text-red-500 animate-in slide-in-from-top-1 fade-in-0 duration-200">
            {errorText}
          </p>
        )}
      </div>
    );
  }
);
Input.displayName = 'Input';

export { Input };
