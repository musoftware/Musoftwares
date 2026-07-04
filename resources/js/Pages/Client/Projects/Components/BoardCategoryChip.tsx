import React from 'react';
import { cn } from '@/lib/utils';

/**
 * Tailwind class fragments for each supported category color. Kept narrow on purpose
 * so the card chip stays readable in dark and light themes. New colors must be added
 * here AND in the backend `create_project_board_categories_table.php` defaults (and the
 * default seed list in `ProjectBoardCategory::DEFAULTS`).
 */
export const CATEGORY_COLOR_CLASSES: Record<string, { bg: string; text: string; ring: string; dot: string }> = {
    rose:   { bg: 'bg-rose-100',   text: 'text-rose-700',   ring: 'ring-rose-200',   dot: 'bg-rose-500' },
    amber:  { bg: 'bg-amber-100',  text: 'text-amber-700',  ring: 'ring-amber-200',  dot: 'bg-amber-500' },
    slate:  { bg: 'bg-slate-100',  text: 'text-slate-600',  ring: 'ring-slate-200',  dot: 'bg-slate-500' },
    sky:    { bg: 'bg-sky-100',    text: 'text-sky-700',    ring: 'ring-sky-200',    dot: 'bg-sky-500' },
    emerald:{ bg: 'bg-emerald-100',text: 'text-emerald-700',ring: 'ring-emerald-200',dot: 'bg-emerald-500' },
    violet: { bg: 'bg-violet-100', text: 'text-violet-700', ring: 'ring-violet-200', dot: 'bg-violet-500' },
    indigo: { bg: 'bg-indigo-100', text: 'text-indigo-700', ring: 'ring-indigo-200', dot: 'bg-indigo-500' },
    fuchsia:{ bg: 'bg-fuchsia-100',text: 'text-fuchsia-700',ring: 'ring-fuchsia-200',dot: 'bg-fuchsia-500' },
    pink:   { bg: 'bg-pink-100',   text: 'text-pink-700',   ring: 'ring-pink-200',   dot: 'bg-pink-500' },
    cyan:   { bg: 'bg-cyan-100',   text: 'text-cyan-700',   ring: 'ring-cyan-200',   dot: 'bg-cyan-500' },
    teal:   { bg: 'bg-teal-100',   text: 'text-teal-700',   ring: 'ring-teal-200',   dot: 'bg-teal-500' },
    lime:   { bg: 'bg-lime-100',   text: 'text-lime-700',   ring: 'ring-lime-200',   dot: 'bg-lime-500' },
    orange: { bg: 'bg-orange-100', text: 'text-orange-700', ring: 'ring-orange-200', dot: 'bg-orange-500' },
    stone:  { bg: 'bg-stone-100',  text: 'text-stone-700',  ring: 'ring-stone-200',  dot: 'bg-stone-500' },
    zinc:   { bg: 'bg-zinc-100',   text: 'text-zinc-700',   ring: 'ring-zinc-200',   dot: 'bg-zinc-500' },
    neutral:{ bg: 'bg-neutral-100',text: 'text-neutral-700',ring: 'ring-neutral-200',dot: 'bg-neutral-500' },
};

export interface BoardCategoryLike {
    id: number;
    slug?: string;
    name: string;
    color?: string | null;
    text_color?: string | null;
}

export const FALLBACK_COLOR = 'slate';

export function categoryPalette(category?: BoardCategoryLike | null): { bg: string; text: string; ring: string; dot: string } {
    if (!category || !category.color) return CATEGORY_COLOR_CLASSES[FALLBACK_COLOR];
    return CATEGORY_COLOR_CLASSES[category.color] ?? CATEGORY_COLOR_CLASSES[FALLBACK_COLOR];
}

export const BoardCategoryChip: React.FC<{
    category?: BoardCategoryLike | null;
    size?: 'xs' | 'sm';
    className?: string;
    showDot?: boolean;
}> = ({ category, size = 'xs', className, showDot = true }) => {
    const palette = categoryPalette(category);
    const padding = size === 'xs' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs';
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full font-extrabold uppercase tracking-wider ring-1 ring-inset shadow-sm',
                padding,
                palette.bg,
                palette.text,
                palette.ring,
                className,
            )}
            title={category?.name ?? ''}
        >
            {showDot && (
                <span className={cn('h-1.5 w-1.5 shrink-0 rounded-full', palette.dot)} aria-hidden />
            )}
            <span className="truncate max-w-[10rem]">{category?.name ?? '—'}</span>
        </span>
    );
};

export default BoardCategoryChip;
