import React from 'react';
import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Check, Pencil, Star, X } from 'lucide-react';
import { SidebarGroup, SidebarGroupAction, SidebarGroupContent, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/Components/ui/sidebar';
import { __ } from '@/lib/i18n';

export type MenuLink = { title: string; url: string; icon: any; badgeCountKey?: string };

/** What a new admin finds at the top before choosing their own. */
export const DEFAULT_FAVORITES = ['/admin/users', '/admin/invoices', '/admin/builder'];

/** Saves the new list on the account; the menu shows it at once, before the server answers. */
export function saveFavorites(urls: string[], show: (urls: string[]) => void) {
    show(urls);
    router.post('/admin/sidebar-favorites', { urls }, { preserveScroll: true, preserveState: true });
}

const toolClass = 'flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground disabled:opacity-30';

/** The star beside every page while the admin arranges the favorites. */
export function StarToggle({ url, favorites, onToggle }: { url: string; favorites: string[]; onToggle: (url: string) => void }) {
    const on = favorites.includes(url);
    return (
        <button type="button" className={toolClass} onClick={() => onToggle(url)} aria-pressed={on} aria-label={__(on ? 'admin.sidebar_favorite_remove' : 'admin.sidebar_favorite_add')}>
            <Star className={`h-4 w-4 ${on ? 'fill-amber-400 text-amber-500' : ''}`} />
        </button>
    );
}

type Props = {
    links: Record<string, MenuLink>;
    favorites: string[];
    editing: boolean;
    onEditing: (editing: boolean) => void;
    onChange: (urls: string[]) => void;
    isActive: (url: string) => boolean;
    badge: (key?: string) => React.ReactNode;
};

/** The admin's own pages at the top of the menu: one press away, in the order they chose. */
export default function SidebarFavorites({ links, favorites, editing, onEditing, onChange, isActive, badge }: Props) {
    const shown = favorites.filter((url) => links[url]);
    const move = (index: number, by: number) => {
        const next = [...shown];
        [next[index], next[index + by]] = [next[index + by], next[index]];
        onChange(next);
    };

    return (
        <SidebarGroup>
            <SidebarGroupLabel className="px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('admin.sidebar_section_favorites')}</SidebarGroupLabel>
            <SidebarGroupAction onClick={() => onEditing(!editing)} aria-pressed={editing} aria-label={__(editing ? 'admin.sidebar_favorites_done' : 'admin.sidebar_favorites_edit')} title={__(editing ? 'admin.sidebar_favorites_done' : 'admin.sidebar_favorites_edit')}>
                {editing ? <Check className="h-4 w-4" /> : <Pencil className="h-4 w-4" />}
            </SidebarGroupAction>
            <SidebarGroupContent>
                {editing && <p className="px-2 pb-2 text-xs text-muted-foreground">{__('admin.sidebar_favorites_hint')}</p>}
                <SidebarMenu>
                    {shown.map((url, index) => {
                        const link = links[url];
                        return (
                            <SidebarMenuItem key={url} className="flex items-center gap-0.5">
                                <SidebarMenuButton isActive={isActive(url)} tooltip={__(link.title)} className="min-w-0 flex-1" render={<Link href={url} />}>
                                    <link.icon className="h-4 w-4" />
                                    <span className="truncate">{__(link.title)}</span>
                                    {!editing && badge(link.badgeCountKey)}
                                </SidebarMenuButton>
                                {editing && (
                                    <>
                                        <button type="button" className={toolClass} disabled={index === 0} onClick={() => move(index, -1)} aria-label={__('admin.sidebar_favorite_up')}><ArrowUp className="h-4 w-4" /></button>
                                        <button type="button" className={toolClass} disabled={index === shown.length - 1} onClick={() => move(index, 1)} aria-label={__('admin.sidebar_favorite_down')}><ArrowDown className="h-4 w-4" /></button>
                                        <button type="button" className={toolClass} onClick={() => onChange(shown.filter((other) => other !== url))} aria-label={__('admin.sidebar_favorite_remove')}><X className="h-4 w-4" /></button>
                                    </>
                                )}
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
