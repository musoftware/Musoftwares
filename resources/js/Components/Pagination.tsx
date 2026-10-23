import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const HTML_ENTITIES: Record<string, string> = {
    '&laquo;': '',
    '&raquo;': '',
    '&hellip;': '...',
    '&amp;': '&',
    '&nbsp;': ' ',
};

/**
 * Laravel paginator labels contain HTML entities ("&laquo; Previous").
 * Render them as plain text instead of injecting HTML.
 */
export function toPlainLabel(label: string, index: number, total: number): string {
    if (index === 0) return __('general.previous');
    if (index === total - 1) return __('general.next_page');
    return Object.entries(HTML_ENTITIES)
        .reduce((text, [entity, plain]) => text.split(entity).join(plain), String(label))
        .trim();
}

/**
 * A paginator sends `links` as a list of page links. A resource collection sends `links` as an object
 * (first, last, prev, next) and keeps the page list in `meta.links`, so only a list is usable here.
 */
export function pageLinks(links: unknown): PaginationLink[] {
    return Array.isArray(links) ? (links as PaginationLink[]) : [];
}

export default function Pagination({ links: rawLinks }: { links: PaginationLink[] | Record<string, unknown> | null | undefined }) {
    const links = pageLinks(rawLinks);
    if (links.length <= 3) return null;

    return (
        <nav className="mt-6 flex justify-center" aria-label={__('general.pagination')}>
            <div className="flex flex-wrap gap-1">
                {links.map((link, index) => (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url || '#'}
                        aria-current={link.active ? 'page' : undefined}
                        className={`rounded-md border px-4 py-2 text-sm ${
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50'
                        } ${!link.url ? 'pointer-events-none cursor-not-allowed opacity-50' : ''}`}
                        preserveScroll
                    >
                        {toPlainLabel(link.label, index, links.length)}
                    </Link>
                ))}
            </div>
        </nav>
    );
}
