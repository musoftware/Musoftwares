import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import { MessageSquareMore, RefreshCw, Send, X, UserRound, Mail } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';
import type { BoardCard, CardType } from './ProjectBoard';

export interface BoardComment {
    id: number;
    body: string;
    author_name?: string | null;
    guest_name?: string | null;
    guest_email?: string | null;
    is_guest?: boolean;
    created_at?: string | null;
}

interface CommentsPopoverProps {
    card: BoardCard;
    projectId: number | string;
    /** True when accessed via /shared-board/{token} for guests. */
    guestMode?: boolean;
    /** Share token used for guest endpoints; required when guestMode is true. */
    shareToken?: string | null;
    /** Optional initial comments payload (avoids round-trip when known). */
    initialComments?: BoardComment[];
    /** Optional initial count to render the badge without a fetch. */
    initialCount?: number;
    /** Compact trigger button (icon only) — defaults to false. */
    iconOnly?: boolean;
    className?: string;
    /** Called whenever the comment count changes (initial fetch, post, etc.). */
    onCountChange?: (count: number) => void;
}

const GUEST_NAME_KEY = 'musoftware.guest.name';
const GUEST_EMAIL_KEY = 'musoftware.guest.email';

function formatRelative(iso?: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString();
}

function endpoint(type: CardType, id: number): string {
    return `comments/${type}/${id}`;
}

export default function CommentsPopover({
    card,
    projectId,
    guestMode = false,
    shareToken = null,
    initialComments,
    initialCount,
    iconOnly = false,
    className,
    onCountChange,
}: CommentsPopoverProps) {
    const [open, setOpen] = useState(false);
    const [comments, setComments] = useState<BoardComment[]>(initialComments ?? []);
    const [loading, setLoading] = useState(false);
    const [sending, setSending] = useState(false);
    const [draft, setDraft] = useState('');
    const [guestName, setGuestName] = useState('');
    const [guestEmail, setGuestEmail] = useState('');
    const listRef = useRef<HTMLDivElement | null>(null);
    const onCountChangeRef = useRef<typeof onCountChange>(onCountChange);
    // null sentinel = "never reported a real count yet".
    // Avoids emitting 0 on mount when the real count comes from `initialCount`.
    const lastReportedCountRef = useRef<number | null>(null);
    const hasInitialCount = typeof initialCount === 'number';

    useEffect(() => {
        onCountChangeRef.current = onCountChange;
    }, [onCountChange]);

    // Read guest identity from localStorage so returning guests only type it once.
    useEffect(() => {
        if (!guestMode) return;
        try {
            const storedName = window.localStorage.getItem(GUEST_NAME_KEY) || '';
            const storedEmail = window.localStorage.getItem(GUEST_EMAIL_KEY) || '';
            if (storedName) setGuestName(storedName);
            if (storedEmail) setGuestEmail(storedEmail);
        } catch (_e) {
            /* localStorage unavailable — silently fall back to manual entry each time */
        }
    }, [guestMode]);

    const count = useMemo(() => comments.length, [comments]);

    // Report count changes to parent only after we have an authoritative value
    // (either an explicit count from the server response, or after the comments
    // list has been fetched at least once). This prevents emitting `0` on mount
    // when `initialCount` was already known, which would clobber the badge.
    useEffect(() => {
        if (lastReportedCountRef.current === null) {
            if (open) {
                lastReportedCountRef.current = hasInitialCount ? initialCount! : count;
                if (lastReportedCountRef.current !== (hasInitialCount ? initialCount! : count)) {
                    onCountChangeRef.current?.(lastReportedCountRef.current);
                }
            }
            return;
        }
        if (count === lastReportedCountRef.current) return;
        lastReportedCountRef.current = count;
        onCountChangeRef.current?.(count);
    }, [count, open, hasInitialCount, initialCount]);

    const fetchComments = useCallback(async () => {
        setLoading(true);
        try {
            let url: string;
            if (guestMode && shareToken) {
                url = route('public.comments.index', {
                    token: shareToken,
                    type: card.type,
                    id: card.id,
                });
            } else {
                url = route('client.projects.comments.index', {
                    project: projectId,
                    type: card.type,
                    id: card.id,
                });
            }
            const { data } = await axios.get<{ comments: BoardComment[]; count?: number }>(url);
            const next = Array.isArray(data?.comments) ? data.comments : [];
            setComments(next);
            if (typeof data?.count === 'number') {
                if (lastReportedCountRef.current !== data.count) {
                    lastReportedCountRef.current = data.count;
                    onCountChangeRef.current?.(data.count);
                }
            }
        } catch (e) {
            // Keep the popover usable even if the list fails — user can still try to post.
            console.warn('Failed to load comments', e);
        } finally {
            setLoading(false);
        }
    }, [card.id, card.type, guestMode, projectId, shareToken, onCountChange]);

    // Lazy-load on first open; refresh whenever the popover re-opens.
    useEffect(() => {
        if (!open) return;
        fetchComments();
    }, [open, fetchComments]);

    // Auto-scroll to the newest comment whenever the list grows.
    useEffect(() => {
        if (!open) return;
        requestAnimationFrame(() => {
            listRef.current?.scrollTo({ top: listRef.current.scrollHeight, behavior: 'smooth' });
        });
    }, [open, comments.length]);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        const body = draft.trim();
        if (!body || sending) return;
        setSending(true);

        const payload: Record<string, unknown> = {
            type: card.type,
            commentable_id: card.id,
            body,
        };

        if (guestMode && !guestName && !guestEmail) {
            const name = guestName.trim();
            const email = guestEmail.trim();
            if (!name || !email) {
                toast.error(__('general.guest_name') + ' / ' + __('general.guest_email') + ' ' + __('general.required'));
                setSending(false);
                return;
            }
            payload.guest_name = name;
            payload.guest_email = email;
        } else if (guestMode) {
            payload.guest_name = guestName.trim();
            payload.guest_email = guestEmail.trim();
        }

        try {
            let url: string;
            if (guestMode && shareToken) {
                url = route('public.comments.store', { token: shareToken });
            } else {
                url = route('client.projects.comments.store', { project: projectId });
            }
            const { data } = await axios.post<{ ok: boolean; comment: BoardComment }>(url, payload);
            if (data?.comment) {
                setComments((prev) => [data.comment, ...prev]);
                setDraft('');
                if (guestMode) {
                    try {
                        window.localStorage.setItem(GUEST_NAME_KEY, guestName.trim());
                        window.localStorage.setItem(GUEST_EMAIL_KEY, guestEmail.trim());
                    } catch (_e) {
                        /* ignore storage failures */
                    }
                }
                toast.success(__('general.comment_posted') || 'Comment posted.');
            }
        } catch (err: any) {
            const message = err?.response?.data?.message || __('general.could_not_post_comment');
            toast.error(message);
        } finally {
            setSending(false);
        }
    };

    const showInitialBadge = typeof initialCount === 'number' && comments.length === 0;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                onClick={(e) => e.stopPropagation()}
                className={cn(
                    'relative inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-bold ring-1 transition-colors focus:outline-none focus:ring-2',
                    iconOnly
                        ? 'h-7 w-7 justify-center bg-sky-50 text-sky-700 ring-sky-200 hover:bg-sky-100 hover:ring-sky-300 focus:ring-sky-400/60'
                        : 'bg-sky-50 px-2.5 text-sky-700 ring-sky-200 hover:bg-sky-100 hover:ring-sky-300 focus:ring-sky-400/60',
                    className,
                )}
                title={__('general.open_comments') || 'Open comments'}
                aria-label={__('general.open_comments') || 'Open comments'}
            >
                <MessageSquareMore className="h-3.5 w-3.5" />
                {!iconOnly && <span>{__('general.comments') || 'Comments'}</span>}
                {showInitialBadge && initialCount! > 0 && (
                    <span className="absolute -top-1.5 -end-1.5 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">
                        {initialCount}
                    </span>
                )}
                {!showInitialBadge && count > 0 && (
                    <span className="ml-0.5 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-sky-700 px-1 text-[10px] font-bold text-white">
                        {count}
                    </span>
                )}
            </PopoverTrigger>
            <PopoverContent
                align="end"
                side="bottom"
                sideOffset={8}
                className="w-[22rem] max-w-[calc(100vw-1.5rem)] p-0"
            >
                <header className="flex items-center justify-between gap-2 rounded-t-lg border-b border-slate-200 bg-gradient-to-b from-sky-50/70 to-white px-4 py-3">
                    <div className="flex items-center gap-2 text-sky-700">
                        <MessageSquareMore className="h-4 w-4" />
                        <div className="flex flex-col leading-tight">
                            <span className="text-[10px] font-bold uppercase tracking-wider">
                                {__('general.comments') || 'Comments'}
                            </span>
                            <span className="line-clamp-1 text-xs font-bold text-slate-900">
                                {card.title}
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={fetchComments}
                        className="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                        title="Refresh"
                    >
                        <RefreshCw className={cn('h-3.5 w-3.5', loading && 'animate-spin')} />
                    </button>
                </header>

                <div
                    ref={listRef}
                    className="max-h-72 min-h-[120px] overflow-y-auto px-4 py-3"
                >
                    {loading && comments.length === 0 ? (
                        <div className="flex items-center justify-center py-6 text-xs text-slate-400">
                            <RefreshCw className="mr-2 h-3.5 w-3.5 animate-spin" />
                            {__('general.loading') || 'Loading…'}
                        </div>
                    ) : comments.length === 0 ? (
                        <p className="py-4 text-center text-xs italic text-slate-400">
                            {__('general.no_comments_yet') || 'No comments yet.'}
                        </p>
                    ) : (
                        <ul className="space-y-3">
                            {comments.map((c) => {
                                const name = c.author_name || c.guest_name || __('general.guest') || 'Guest';
                                const initials = name
                                    .split(/\s+/)
                                    .map((part) => part.charAt(0))
                                    .join('')
                                    .slice(0, 2)
                                    .toUpperCase();
                                return (
                                    <li
                                        key={c.id}
                                        className="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-slate-50/60 p-2.5"
                                    >
                                        <span className="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white shadow-sm">
                                            {initials || '?'}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="truncate text-[11px] font-bold text-slate-800">
                                                    {name}
                                                </span>
                                                {c.is_guest && (
                                                    <span className="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700 ring-1 ring-amber-200">
                                                        {__('general.guest') || 'Guest'}
                                                    </span>
                                                )}
                                                <span className="ms-auto text-[10px] text-slate-400">
                                                    {formatRelative(c.created_at)}
                                                </span>
                                            </div>
                                            <p className="mt-1 whitespace-pre-wrap break-words text-xs leading-relaxed text-slate-700">
                                                {c.body}
                                            </p>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-2 rounded-b-lg border-t border-slate-200 bg-white px-4 py-3"
                >
                    {guestMode && (
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label htmlFor={`guest-name-${card.type}-${card.id}`} className="sr-only">
                                    {__('general.your_name')}
                                </Label>
                                <div className="relative">
                                    <UserRound className="pointer-events-none absolute start-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        id={`guest-name-${card.type}-${card.id}`}
                                        value={guestName}
                                        onChange={(e) => setGuestName(e.target.value)}
                                        placeholder={__('general.your_name') || 'Your name'}
                                        maxLength={120}
                                        required
                                        className="h-8 ps-8 text-xs"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor={`guest-email-${card.type}-${card.id}`} className="sr-only">
                                    {__('general.your_email')}
                                </Label>
                                <div className="relative">
                                    <Mail className="pointer-events-none absolute start-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        id={`guest-email-${card.type}-${card.id}`}
                                        type="email"
                                        value={guestEmail}
                                        onChange={(e) => setGuestEmail(e.target.value)}
                                        placeholder={__('general.your_email') || 'Your email'}
                                        maxLength={190}
                                        required
                                        className="h-8 ps-8 text-xs"
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                    <div className="flex items-end gap-2">
                        <Textarea
                            value={draft}
                            onChange={(e) => setDraft(e.target.value)}
                            onKeyDown={(e) => {
                                if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    (e.currentTarget.form as HTMLFormElement | null)?.requestSubmit();
                                }
                            }}
                            placeholder={__('general.write_a_comment') || 'Write a comment…'}
                            rows={2}
                            maxLength={5000}
                            className="min-h-[64px] flex-1 resize-none text-xs"
                        />
                        <Button
                            type="submit"
                            disabled={sending || !draft.trim()}
                            className="h-9 gap-1 bg-sky-600 px-3 text-xs font-semibold text-white hover:bg-sky-700"
                        >
                            <Send className="h-3.5 w-3.5" />
                            <span className="sr-only">{__('general.send') || 'Send'}</span>
                        </Button>
                    </div>
                </form>
            </PopoverContent>
        </Popover>
    );
}
