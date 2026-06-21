import { cn } from '@/lib/utils';
import { Loader2, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export function SearchInput({
    value,
    onChange,
    placeholder = 'Search...',
    loading = false,
    className,
}) {
    const [localValue, setLocalValue] = useState(value || '');
    const timerRef = useRef(null);

    useEffect(() => {
        setLocalValue(value || '');
    }, [value]);

    const handleChange = (e) => {
        const newValue = e.target.value;
        setLocalValue(newValue);

        if (timerRef.current) clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => {
            onChange(newValue);
        }, 300);
    };

    const handleClear = () => {
        setLocalValue('');
        if (timerRef.current) clearTimeout(timerRef.current);
        onChange('');
    };

    return (
        <div className={cn('relative flex items-center', className)}>
            <div className="text-text-muted absolute start-3 flex items-center justify-center">
                {loading ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <Search className="h-4 w-4" />
                )}
            </div>
            <input
                type="text"
                value={localValue}
                onChange={handleChange}
                placeholder={placeholder}
                className="bg-surface border-border focus:border-primary focus:ring-primary placeholder:text-text-muted h-9 w-full rounded-md border pe-8 ps-9 text-[13px] shadow-sm transition-colors focus:ring-1"
            />
            {localValue && (
                <button
                    type="button"
                    onClick={handleClear}
                    className="text-text-muted hover:text-text-primary absolute end-3 flex items-center justify-center transition-colors"
                >
                    <X className="h-3.5 w-3.5" />
                </button>
            )}
        </div>
    );
}
