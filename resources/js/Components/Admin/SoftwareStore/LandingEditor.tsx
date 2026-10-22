import React from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { FIELD_LABELS, ICON_NAMES, SECTION_LABELS, labelOf, t } from './t';

type Json = any;

const LONG_TEXT_KEYS = new Set(['body', 'subheadline', 'note', 't']);
const TOP_KEYS = ['kicker', 'headline', 'subheadline'];

const blankLike = (sample: Json): Json => {
    if (Array.isArray(sample)) return [];
    if (sample && typeof sample === 'object') {
        return Object.fromEntries(Object.entries(sample).map(([key, value]) => [key, blankLike(value)]));
    }
    return '';
};

function TextField({ name, value, onChange }: { name: string; value: string; onChange: (v: string) => void }) {
    const label = labelOf(FIELD_LABELS, name);

    if (name === 'icon') {
        return (
            <label className="block space-y-1 text-sm">
                <span>{label}</span>
                <select className="w-full h-9 rounded-md border px-2 text-sm bg-background" value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                    {ICON_NAMES.map((icon) => (
                        <option key={icon} value={icon}>{icon}</option>
                    ))}
                </select>
            </label>
        );
    }

    return (
        <label className="block space-y-1 text-sm">
            <span>{label}</span>
            {LONG_TEXT_KEYS.has(name) ? (
                <Textarea rows={3} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
            ) : (
                <Input value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
            )}
        </label>
    );
}

function StringList({ name, value, onChange }: { name: string; value: string[]; onChange: (v: string[]) => void }) {
    return (
        <div className="space-y-2 text-sm">
            <span>{labelOf(FIELD_LABELS, name)}</span>
            {value.map((line, index) => (
                <div key={index} className="flex gap-2">
                    <Input value={line} onChange={(e) => onChange(value.map((v, i) => (i === index ? e.target.value : v)))} />
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange(value.filter((_, i) => i !== index))}>
                        {t('Remove', 'حذف')}
                    </Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => onChange([...value, ''])}>
                {t('Add line', 'إضافة سطر')}
            </Button>
        </div>
    );
}

function Repeater({ name, value, onChange }: { name: string; value: Json[]; onChange: (v: Json[]) => void }) {
    const template = blankLike(value[0] ?? {});

    return (
        <div className="space-y-3 text-sm">
            <span className="font-medium">{labelOf(FIELD_LABELS, name)}</span>
            {value.map((item, index) => (
                <div key={index} className="rounded-md border p-3 space-y-3 bg-muted/20">
                    <Group value={item} onChange={(next) => onChange(value.map((v, i) => (i === index ? next : v)))} />
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange(value.filter((_, i) => i !== index))}>
                        {t('Remove this item', 'حذف العنصر ده')}
                    </Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => onChange([...value, template])}>
                {t('Add item', 'إضافة عنصر')}
            </Button>
        </div>
    );
}

function Node({ name, value, onChange }: { name: string; value: Json; onChange: (v: Json) => void }) {
    if (Array.isArray(value)) {
        const onlyText = value.every((v) => typeof v === 'string');
        return onlyText ? (
            <StringList name={name} value={value} onChange={onChange} />
        ) : (
            <Repeater name={name} value={value} onChange={onChange} />
        );
    }

    if (value && typeof value === 'object') {
        return (
            <div className="space-y-3">
                <p className="text-sm font-medium">{labelOf(FIELD_LABELS, name)}</p>
                <Group value={value} onChange={onChange} />
            </div>
        );
    }

    return <TextField name={name} value={value} onChange={onChange} />;
}

function Group({ value, onChange }: { value: Record<string, Json>; onChange: (v: Record<string, Json>) => void }) {
    return (
        <div className="space-y-3">
            {Object.entries(value).map(([key, child]) => (
                <Node key={key} name={key} value={child} onChange={(next) => onChange({ ...value, [key]: next })} />
            ))}
        </div>
    );
}

/** Friendly editor for the text of one language of a landing page. No JSON shown. */
export function LandingEditor({ copy, order, onChange }: { copy: Record<string, Json>; order: string[]; onChange: (v: Record<string, Json>) => void }) {
    const set = (key: string, next: Json) => onChange({ ...copy, [key]: next });
    const sectionKeys = [...new Set([...order, ...Object.keys(copy), 'cta'])].filter((key) => copy[key] && typeof copy[key] === 'object');

    return (
        <div className="space-y-6">
            <div className="rounded-md border p-4 space-y-3">
                <h3 className="font-semibold">{t('Top of the page', 'أعلى الصفحة')}</h3>
                {TOP_KEYS.map((key) => (
                    <TextField key={key} name={key} value={copy[key] ?? ''} onChange={(v) => set(key, v)} />
                ))}
            </div>
            {sectionKeys.map((key) => (
                <div key={key} className="rounded-md border p-4 space-y-3">
                    <h3 className="font-semibold">{labelOf(SECTION_LABELS, key)}</h3>
                    <Group value={copy[key]} onChange={(next) => set(key, next)} />
                </div>
            ))}
        </div>
    );
}
