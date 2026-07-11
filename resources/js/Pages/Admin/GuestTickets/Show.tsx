import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeft, ArrowUpRight, Mail } from 'lucide-react';
import { __ } from '@/lib/i18n';
import MessageBubble from '@/Components/Admin/GuestTicket/MessageBubble';

interface Props {
    ticket: any;
    messages: any[];
    statuses: string[];
}

export default function Show({ ticket, messages, statuses }: Props) {
    const reply = useForm({
        body: '',
        attachment: null as File | null,
    });

    const [status, setStatus] = useState(ticket.status);

    const submitReply = (e: React.FormEvent) => {
        e.preventDefault();
        if (! reply.data.body.trim()) return;
        reply.post(route('admin.guest-tickets.reply', ticket.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reply.reset(),
        });
    };

    const updateStatus = (next: string) => {
        setStatus(next);
        router.post(route('admin.guest-tickets.updateStatus', ticket.id), { status: next }, {
            preserveScroll: true,
        });
    };

    const mailto = `mailto:${ticket.email}?subject=${encodeURIComponent('Re: ' + (ticket.subject ?? ''))}`;

    return (
        <AdminSidebarLayout
            header={`Ticket #${ticket.id}`}
            actions={
                <Link href={route('admin.guest-tickets.index')}>
                    <Button variant="outline"><ArrowLeft className="w-4 h-4 me-2" /> {__('general.back')}</Button>
                </Link>
            }
        >
            <Head title={`Ticket #${ticket.id}`} />
            <div className="max-w-5xl mx-auto space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-slate-100 pb-6">
                        <div>
                            <p className="text-sm text-slate-500">{__('general.name')}</p>
                            <p className="font-medium">{ticket.name}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">{__('general.email')}</p>
                            <p className="font-medium">{ticket.email}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">{__('general.mobile')}</p>
                            <p className="font-medium">{ticket.mobile || '—'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">{__('general.subject')}</p>
                            <p className="font-medium">{ticket.subject || '—'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">{__('general.status')}</p>
                            <div className="flex items-center gap-2">
                                <Select value={status} onValueChange={updateStatus}>
                                    <SelectTrigger className="w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((s) => (
                                            <SelectItem key={s} value={s}>{__(`general.status_${s}`)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <a href={mailto} target="_blank" rel="noreferrer">
                                    <Button variant="ghost" size="sm"><Mail className="w-4 h-4 me-1" /> {__('general.open_in_email_client')}</Button>
                                </a>
                            </div>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">{__('general.date')}</p>
                            <p className="font-medium">{new Date(ticket.created_at).toLocaleString()}</p>
                        </div>
                    </div>

                    <div className="mt-6">
                        <h2 className="text-base font-semibold mb-3">{__('general.thread')}</h2>
                        {messages.length === 0 ? (
                            <p className="text-sm text-slate-500 text-center py-8 border border-dashed border-slate-200 rounded-lg">
                                {__('general.no_messages_yet')}
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {messages.map((m) => (
                                    <MessageBubble
                                        key={m.id}
                                        direction={m.direction}
                                        fromName={m.from_email === ticket.email ? ticket.name : null}
                                        fromEmail={m.from_email}
                                        toEmail={m.to_email}
                                        subject={m.subject}
                                        bodyText={m.body_text}
                                        bodyHtml={m.body_html}
                                        attachments={m.attachments_json ?? []}
                                        sentAt={m.sent_at}
                                        receivedAt={m.received_at}
                                        messageId={m.message_id}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 className="text-base font-semibold mb-3">{__('general.reply')}</h2>
                    <form onSubmit={submitReply} className="space-y-3">
                        <Textarea
                            value={reply.data.body}
                            onChange={(e) => reply.setData('body', e.target.value)}
                            placeholder={__('general.reply_placeholder')}
                            rows={6}
                            required
                        />
                        <div className="flex flex-wrap items-center gap-3">
                            <Input
                                type="file"
                                onChange={(e) => reply.setData('attachment', e.target.files?.[0] ?? null)}
                                className="max-w-xs"
                            />
                            <Button type="submit" disabled={reply.processing}>
                                <ArrowUpRight className="w-4 h-4 me-2" />
                                {reply.processing ? __('general.loading') : __('general.send_reply')}
                            </Button>
                        </div>
                        {reply.errors.body && (
                            <p className="text-rose-600 text-sm">{reply.errors.body}</p>
                        )}
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
