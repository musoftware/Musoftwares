import React from 'react';
import { Button } from '@/Components/ui/button';
import { SECTION_LABELS, labelOf, t } from './t';

/** Choose which blocks the landing page shows and in which order. */
export function SectionsEditor({ all, shown, onChange }: { all: string[]; shown: string[]; onChange: (shown: string[]) => void }) {
    const rows = [...shown, ...all.filter((key) => !shown.includes(key))];

    const toggle = (key: string) => onChange(shown.includes(key) ? shown.filter((k) => k !== key) : [...shown, key]);
    const move = (key: string, step: number) => {
        const index = shown.indexOf(key);
        const target = index + step;
        if (index < 0 || target < 0 || target >= shown.length) return;
        const next = [...shown];
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next);
    };

    return (
        <div className="space-y-2">
            {rows.map((key) => (
                <div key={key} className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm">
                    <label className="flex items-center gap-3">
                        <input type="checkbox" checked={shown.includes(key)} onChange={() => toggle(key)} />
                        <span>{labelOf(SECTION_LABELS, key)}</span>
                    </label>
                    <span className="flex gap-2">
                        <Button type="button" variant="outline" size="sm" disabled={!shown.includes(key)} onClick={() => move(key, -1)}>
                            {t('Up', 'فوق')}
                        </Button>
                        <Button type="button" variant="outline" size="sm" disabled={!shown.includes(key)} onClick={() => move(key, 1)}>
                            {t('Down', 'تحت')}
                        </Button>
                    </span>
                </div>
            ))}
        </div>
    );
}
