import React, { useEffect, useRef } from 'react';
import { AlertCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

/**
 * Every error of a form in one box at its top. Long forms show a message only under some fields,
 * so without this an error lower down (a rate, a date, a slug) looked like nothing happened.
 * The fields with an error are marked (aria-invalid, which the inputs style in red), and the first
 * one is scrolled into view and focused. Field ids are expected to match the form keys.
 */
export default function FormErrorSummary({ errors = {} }) {
    const box = useRef(null);
    const keys = Object.keys(errors);

    useEffect(() => {
        document.querySelectorAll('[data-error-marked]').forEach((el) => {
            el.removeAttribute('aria-invalid');
            el.removeAttribute('data-error-marked');
        });
        if (!keys.length) return;

        keys.forEach((key) => {
            const field = document.getElementById(key);
            if (field) {
                field.setAttribute('aria-invalid', 'true');
                field.setAttribute('data-error-marked', '1');
            }
        });

        // the field highest on the page, whatever order the server listed the errors in
        const first = keys.map((key) => document.getElementById(key)).filter(Boolean)
            .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))[0];
        (first || box.current)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        first?.focus({ preventScroll: true });
    }, [JSON.stringify(errors)]);

    if (!keys.length) return null;

    return (
        <div ref={box} role="alert" className="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p className="flex items-center gap-2 font-semibold">
                <AlertCircle className="h-4 w-4 shrink-0" />
                {__('general.please_fix_the_errors_below')}
            </p>
            <ul className="mt-2 list-disc space-y-1 ps-6">
                {keys.map((key) => (
                    <li key={key}>
                        <button type="button" className="text-start underline-offset-2 hover:underline" onClick={() => document.getElementById(key)?.focus()}>
                            {errors[key]}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
