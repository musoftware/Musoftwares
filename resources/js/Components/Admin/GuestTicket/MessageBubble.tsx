import React from 'react';
import { ArrowDownLeft, ArrowUpRight, Mail, Clock } from 'lucide-react';
import { __ } from '@/lib/i18n';

type Direction = 'inbound' | 'outbound';

export interface MessageBubbleProps {
    direction: Direction;
    fromName?: string | null;
    fromEmail?: string | null;
    toEmail?: string | null;
    subject?: string | null;
    bodyText?: string | null;
    bodyHtml?: string | null;
    attachments?: Array<{ name: string; mime?: string; size?: number; path?: string }>;
    sentAt?: string | null;
    receivedAt?: string | null;
    messageId?: string | null;
}

export default function MessageBubble(props: MessageBubbleProps) {
    const isInbound = props.direction === 'inbound';
    const sentAt = props.sentAt || props.receivedAt;
    const ariaLabel = isInbound
        ? `Inbound message from ${props.fromName ?? props.fromEmail ?? 'guest'}`
        : `Outbound reply to ${props.toEmail ?? 'guest'}`;

    return (
        <article
            role="article"
            aria-label={ariaLabel}
            className={`flex ${isInbound ? 'justify-start' : 'justify-end'}`}
        >
            <div
                className={`max-w-[80%] rounded-2xl border shadow-sm ${
                    isInbound
                        ? 'bg-white border-slate-200 text-slate-800'
                        : 'bg-slate-900 border-slate-900 text-white'
                }`}
            >
                <header
                    className={`flex items-center gap-2 px-4 py-2 text-xs ${isInbound ? 'text-slate-500 border-b border-slate-100' : 'text-slate-300 border-b border-slate-800'}`}
                >
                    {isInbound ? <ArrowDownLeft className="w-4 h-4" /> : <ArrowUpRight className="w-4 h-4" />}
                    <span className="font-semibold">
                        {isInbound
                            ? props.fromName || props.fromEmail || 'Guest'
                            : __('general.admin') || 'Admin'}
                    </span>
                    <span aria-hidden>·</span>
                    <span className="truncate">{isInbound ? props.fromEmail : props.toEmail}</span>
                    {sentAt && (
                        <span className="ms-auto inline-flex items-center gap-1">
                            <Clock className="w-3 h-3" />
                            <time dateTime={sentAt}>{new Date(sentAt).toLocaleString()}</time>
                        </span>
                    )}
                </header>

                {props.subject && (
                    <div className={`px-4 pt-3 text-sm font-semibold ${isInbound ? 'text-slate-900' : 'text-white'}`}>
                        {props.subject}
                    </div>
                )}

                <div className={`px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap break-words ${isInbound ? 'text-slate-700' : 'text-slate-100'}`}>
                    {props.bodyHtml
                        ? <div dangerouslySetInnerHTML={{ __html: props.bodyHtml }} />
                        : (props.bodyText ?? '')}
                </div>

                {props.attachments && props.attachments.length > 0 && (
                    <ul className={`px-4 pb-3 flex flex-wrap gap-2`}>
                        {props.attachments.map((a, i) => (
                            <li
                                key={i}
                                className={`inline-flex items-center gap-1 text-xs px-2 py-1 rounded ${isInbound ? 'bg-slate-100 text-slate-700' : 'bg-slate-800 text-slate-100'}`}
                            >
                                <Mail className="w-3 h-3" />
                                <a
                                    className="hover:underline"
                                    href={a.path ? `/storage/${a.path}` : undefined}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    {a.name}
                                </a>
                                {a.size != null && <span className="opacity-70">{Math.round(a.size / 1024)} KB</span>}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </article>
    );
}
