import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { toast } from 'sonner';
import { 
    Mail, 
    Copy, 
    ChevronDown, 
    ChevronUp, 
    FileText, 
    ExternalLink, 
    Clock, 
    AlertTriangle, 
    AlertCircle, 
    CheckCircle2, 
    ShieldAlert,
    Lock,
    Folder,
    DollarSign,
    Receipt
} from 'lucide-react';

interface DsoData {
    oldest_invoice_id: number | string;
    oldest_invoice_created_at: string;
    age_days: number;
    deadline_iso: string;
    days_remaining: number;
    status: 'safe' | 'warning_1' | 'warning_2' | 'suspended';
}

interface ClientDueData {
    id: number;
    name: string;
    email: string;
    lang: string;
    unpaid_count: number;
    dues_summary: string;
    default_subject: string;
    default_body: string;
    dso_data: DsoData | null;
    invoices: Array<{
        id: number | string;
        unpaid: number;
        formatted_unpaid: string;
        created_at: string;
        items: Array<{
            title: string;
            amount: number;
            qty: number;
        }>;
    }>;
}

interface Props {
    clients: ClientDueData[];
    dso_enabled: boolean;
    global_dso_limit: number;
}

interface DsoCountdownProps {
    dsoData: DsoData;
    globalLimit: number;
}

function DsoCountdown({ dsoData, globalLimit }: DsoCountdownProps) {
    const [timeLeft, setTimeLeft] = useState<{
        days: number;
        hours: number;
        minutes: number;
        seconds: number;
        isOverdue: boolean;
    } | null>(null);

    React.useEffect(() => {
        const calculateTimeLeft = () => {
            const targetMs = new Date(dsoData.deadline_iso).getTime();
            const now = Date.now();
            const diffMs = targetMs - now;

            if (diffMs <= 0) {
                setTimeLeft({ days: 0, hours: 0, minutes: 0, seconds: 0, isOverdue: true });
                return;
            }

            const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);

            setTimeLeft({ days, hours, minutes, seconds, isOverdue: false });
        };

        calculateTimeLeft();
        const interval = setInterval(calculateTimeLeft, 1000);
        return () => clearInterval(interval);
    }, [dsoData.deadline_iso]);

    if (!timeLeft) return null;

    // Calculate progress percentage
    const progressPercent = Math.min(100, Math.max(0, (dsoData.age_days / globalLimit) * 100));

    // Determine colors and styles based on status
    let statusConfig = {
        bg: 'bg-emerald-50/50 text-emerald-700 border-emerald-100',
        progressBarBg: 'bg-emerald-500',
        icon: <CheckCircle2 className="h-4 w-4 text-emerald-600" />,
        textClass: 'text-emerald-700',
        badgeText: __('general.dso_safe') || 'DSO Safe',
    };

    if (dsoData.status === 'warning_1') {
        statusConfig = {
            bg: 'bg-amber-50/50 text-amber-700 border-amber-200 animate-pulse',
            progressBarBg: 'bg-amber-500',
            icon: <AlertTriangle className="h-4 w-4 text-amber-600" />,
            textClass: 'text-amber-700 font-semibold',
            badgeText: __('general.dso_limit_warning_1') || 'Warning: 2 Days Left',
        };
    } else if (dsoData.status === 'warning_2') {
        statusConfig = {
            bg: 'bg-orange-50/50 text-orange-800 border-orange-300 animate-pulse',
            progressBarBg: 'bg-orange-600',
            icon: <AlertCircle className="h-4 w-4 text-orange-600" />,
            textClass: 'text-orange-800 font-bold',
            badgeText: __('general.dso_limit_warning_2') || 'Critical Warning: 1 Day Left',
        };
    } else if (dsoData.status === 'suspended' || timeLeft.isOverdue) {
        statusConfig = {
            bg: 'bg-rose-50/50 text-rose-700 border-rose-200',
            progressBarBg: 'bg-rose-600',
            icon: <ShieldAlert className="h-4 w-4 text-rose-600 animate-bounce" />,
            textClass: 'text-rose-700 font-bold',
            badgeText: __('general.dso_suspended') || 'Serials Automatically Suspended',
        };
    }

    return (
        <div className="w-full mt-4 p-4 rounded-xl border border-slate-100 bg-white/70 backdrop-blur-sm shadow-sm space-y-3">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    {statusConfig.icon}
                    <span className={`text-xs font-semibold uppercase tracking-wider ${statusConfig.textClass}`}>
                        {statusConfig.badgeText}
                    </span>
                </div>
                <div className="text-[11px] text-slate-400 font-mono">
                    {__('general.oldest_invoice') || 'Oldest Invoice'}: <span className="font-semibold text-slate-600">#{dsoData.oldest_invoice_id}</span> ({dsoData.oldest_invoice_created_at.substring(0, 10)})
                </div>
            </div>

            {/* Progress Bar */}
            <div className="space-y-1">
                <div className="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div 
                        className={`h-full transition-all duration-500 ease-out ${statusConfig.progressBarBg}`}
                        style={{ width: `${progressPercent}%` }}
                    />
                </div>
                <div className="flex justify-between text-[10px] text-slate-400 font-mono">
                    <span>0 {__('general.dso_days') || 'Days'}</span>
                    <span className="font-semibold text-slate-600">{dsoData.age_days} / {globalLimit} {__('general.dso_days') || 'Days'}</span>
                    <span>{globalLimit} {__('general.dso_days') || 'Days'}</span>
                </div>
            </div>

            {/* Countdown / Remaining Time */}
            <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                <span className="text-xs text-slate-500 font-medium flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5 text-slate-400" />
                    {__('general.dso_time_remaining') || 'DSO Time Remaining'}:
                </span>
                {timeLeft.isOverdue ? (
                    <span className="text-xs font-bold text-rose-600 uppercase tracking-wide bg-rose-50 border border-rose-100 px-2 py-0.5 rounded animate-pulse">
                        {__('general.dso_suspended') || 'Serials Automatically Suspended'}
                    </span>
                ) : (
                    <div className="flex gap-1.5 text-slate-700">
                        {timeLeft.days > 0 && (
                            <div className="flex items-baseline gap-0.5">
                                <span className="font-bold font-mono text-sm">{timeLeft.days}</span>
                                <span className="text-[10px] text-slate-400">d</span>
                            </div>
                        )}
                        <div className="flex items-baseline gap-0.5">
                            <span className="font-bold font-mono text-sm">{String(timeLeft.hours).padStart(2, '0')}</span>
                            <span className="text-[10px] text-slate-400">h</span>
                        </div>
                        <div className="flex items-baseline gap-0.5">
                            <span className="font-bold font-mono text-sm">{String(timeLeft.minutes).padStart(2, '0')}</span>
                            <span className="text-[10px] text-slate-400">m</span>
                        </div>
                        <div className="flex items-baseline gap-0.5">
                            <span className="font-bold font-mono text-sm text-slate-900 animate-pulse">{String(timeLeft.seconds).padStart(2, '0')}</span>
                            <span className="text-[10px] text-slate-400">s</span>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

export default function DuesBoard({ clients, dso_enabled, global_dso_limit }: Props) {
    const [emailModalClient, setEmailModalClient] = useState<ClientDueData | null>(null);
    const [emailSubject, setEmailSubject] = useState('');
    const [emailBody, setEmailBody] = useState('');
    const [sending, setSending] = useState(false);
    const [expandedClients, setExpandedClients] = useState<Record<number, boolean>>({});

    const toggleClientExpanded = (clientId: number) => {
        setExpandedClients((prev) => ({ ...prev, [clientId]: !prev[clientId] }));
    };

    const handleOpenEmailModal = (client: ClientDueData) => {
        setEmailModalClient(client);
        setEmailSubject(client.default_subject);
        setEmailBody(client.default_body);
    };

    const handleSendEmail = (e: React.FormEvent) => {
        e.preventDefault();
        if (!emailModalClient) return;

        setSending(true);
        // Under Inertia v2 or standard Inertia.js, we can resolve route by helper or pass standard path
        router.post(`/admin/invoices/dues/${emailModalClient.id}/send-reminder`, {
            subject: emailSubject,
            body: emailBody,
        }, {
            onSuccess: () => {
                toast.success(__('general.reminder_sent_successfully') || 'Reminder sent successfully!');
                setEmailModalClient(null);
                setSending(false);
            },
            onError: (errors: any) => {
                toast.error(errors.error || __('general.error_sending_reminder') || 'Failed to send reminder.');
                setSending(false);
            }
        });
    };

    const handleCopySummary = (client: ClientDueData) => {
        const isArabic = client.lang === 'ar';
        let text = '';
        
        if (isArabic) {
            text = `المستحقات المتبقية للعميل: ${client.name}\n\n`;
            text += `تفاصيل الفواتير غير المدفوعة:\n`;
            text += `---------------------------------\n`;
            client.invoices.forEach((inv) => {
                text += `• فاتورة رقم: ${inv.id}\n`;
                text += `  التاريخ: ${inv.created_at}\n`;
                text += `  المبلغ المتبقي: ${inv.formatted_unpaid}\n`;
                text += `  البنود:\n`;
                inv.items.forEach((item) => {
                    text += `  - ${item.title} (الكمية: ${item.qty})\n`;
                });
                text += `---------------------------------\n`;
            });
            text += `إجمالي المستحقات:\n`;
            text += `- ${client.dues_summary.split(' | ').join('\n- ')}\n`;
        } else {
            text = `Outstanding balance for client: ${client.name}\n\n`;
            text += `Unpaid Invoices Details:\n`;
            text += `---------------------------------\n`;
            client.invoices.forEach((inv) => {
                text += `• Invoice ID: ${inv.id}\n`;
                text += `  Date: ${inv.created_at}\n`;
                text += `  Outstanding Amount: ${inv.formatted_unpaid}\n`;
                text += `  Items:\n`;
                inv.items.forEach((item) => {
                    text += `  - ${item.title} (Qty: ${item.qty})\n`;
                });
                text += `---------------------------------\n`;
            });
            text += `Total Due Balance:\n`;
            text += `- ${client.dues_summary.split(' | ').join('\n- ')}\n`;
        }

        navigator.clipboard.writeText(text);
        toast.success(__('general.dues_summary_copied') || 'Summary copied to clipboard successfully!');
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('general.clients_dues_board') || 'Clients Dues Board'} />

            <div className="space-y-6 max-w-7xl mx-auto p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                        {__('general.clients_dues_board') || 'Clients Dues Board'}
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        {__('general.clients_dues_board_desc') || 'A simplified list of clients with outstanding invoice balances, with quick copy and reminder capabilities.'}
                    </p>
                </div>

                {!dso_enabled && (
                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3 text-amber-800 shadow-sm animate-pulse">
                        <AlertTriangle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
                        <div>
                            <h4 className="font-semibold text-sm">{__('general.dso_system_disabled') || 'DSO System is disabled'}</h4>
                            <p className="text-xs text-amber-700 mt-0.5">
                                {__('general.enable_dso_system_help') || 'The collection limit warning and automatic serial suspension system is currently turned off in settings.'}
                            </p>
                        </div>
                    </div>
                )}

                {clients.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center p-12 text-center">
                            <FileText className="h-12 w-12 text-slate-300 mb-4" />
                            <h3 className="font-semibold text-slate-700 text-lg">
                                {__('general.no_unpaid_invoices') || 'No Unpaid Invoices'}
                            </h3>
                            <p className="text-sm text-slate-400 mt-1">
                                {__('general.all_clients_fully_paid') || 'All clients have fully settled their invoices.'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6">
                        {clients.map((client) => {
                            const isExpanded = !!expandedClients[client.id];
                            return (
                                <Card key={client.id} className="overflow-hidden border-slate-200 shadow-sm transition hover:shadow-md">
                                    <div className="bg-slate-50/50 border-b border-slate-100 p-5 md:p-6">
                                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div className="flex items-center gap-4">
                                                <Avatar className="h-10 w-10 bg-slate-100 border border-slate-200 text-slate-700 font-semibold">
                                                    <AvatarFallback>{client.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <h3 className="font-semibold text-slate-800">{client.name}</h3>
                                                        <Badge variant="secondary" className="text-[10px] px-2 py-0.5 rounded-full font-mono uppercase">
                                                            {client.lang}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-xs text-slate-400">{client.email}</p>
                                                </div>
                                            </div>

                                            <div className="flex flex-col md:items-end gap-1">
                                                <div className="text-xs text-slate-400">
                                                    {client.unpaid_count} {client.unpaid_count === 1 ? __('general.invoice') || 'Invoice' : __('general.invoices') || 'Invoices'}
                                                </div>
                                                <div className="font-mono font-bold text-sm text-rose-600 bg-rose-50 border border-rose-100 rounded px-2.5 py-0.5 mt-0.5">
                                                    {client.dues_summary}
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2 self-start md:self-center">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleCopySummary(client)}
                                                    className="gap-1.5 text-xs text-slate-700 hover:bg-slate-100"
                                                >
                                                    <Copy className="h-3.5 w-3.5" />
                                                    {__('general.copy_dues_summary') || 'Copy Summary'}
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleOpenEmailModal(client)}
                                                    className="gap-1.5 text-xs text-slate-700 hover:bg-slate-100"
                                                >
                                                    <Mail className="h-3.5 w-3.5" />
                                                    {__('general.send_email_reminder') || 'Send Email'}
                                                </Button>

                                                {/* User Management Quick Links */}
                                                <div className="flex bg-slate-100 dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700 rounded-lg p-0.5 gap-0.5">
                                                    <Button
                                                        variant="ghost"
                                                        size="xs"
                                                        asChild
                                                        className="h-7 w-7 p-0 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-black dark:hover:text-white"
                                                    >
                                                        <a
                                                            href={`/admin/users/${client.id}/notes`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title={__('general.secure_notes') || 'Secure Notes'}
                                                        >
                                                            <Lock className="h-3.5 w-3.5" />
                                                        </a>
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="xs"
                                                        asChild
                                                        className="h-7 w-7 p-0 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-black dark:hover:text-white"
                                                    >
                                                        <a
                                                            href={`/admin/users/${client.id}/files`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title={__('general.files') || 'Files'}
                                                        >
                                                            <Folder className="h-3.5 w-3.5" />
                                                        </a>
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="xs"
                                                        asChild
                                                        className="h-7 w-7 p-0 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-black dark:hover:text-white"
                                                    >
                                                        <a
                                                            href={`/admin/transactions/create?user=${client.id}&type=receive`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title={__('general.receive_amount') || 'Receive Amount'}
                                                        >
                                                            <DollarSign className="h-3.5 w-3.5" />
                                                        </a>
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="xs"
                                                        asChild
                                                        className="h-7 w-7 p-0 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-black dark:hover:text-white"
                                                    >
                                                        <a
                                                            href={`/admin/invoices/unpaid?client_id=${client.id}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title={__('general.due_invoices') || 'Due Invoices'}
                                                        >
                                                            <Receipt className="h-3.5 w-3.5" />
                                                        </a>
                                                    </Button>
                                                </div>

                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => toggleClientExpanded(client.id)}
                                                    className="p-2 hover:bg-slate-100"
                                                >
                                                    {isExpanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                                </Button>
                                            </div>
                                        </div>

                                        {dso_enabled && client.dso_data && (
                                            <DsoCountdown dsoData={client.dso_data} globalLimit={global_dso_limit} />
                                        )}
                                    </div>

                                    {isExpanded && (
                                        <div className="border-t border-slate-100">
                                            <Table>
                                                <TableHeader className="bg-slate-50/20">
                                                    <TableRow>
                                                        <TableHead className="w-[120px] font-semibold text-xs text-slate-500">{__('general.id') || 'ID'}</TableHead>
                                                        <TableHead className="w-[120px] font-semibold text-xs text-slate-500">{__('general.date') || 'Date'}</TableHead>
                                                        <TableHead className="font-semibold text-xs text-slate-500">{__('general.items') || 'Items'}</TableHead>
                                                        <TableHead className="w-[120px] font-semibold text-xs text-slate-500 text-right">{__('general.unpaid') || 'Unpaid'}</TableHead>
                                                        <TableHead className="w-[80px]"></TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {client.invoices.map((inv) => (
                                                        <TableRow key={inv.id} className="hover:bg-slate-50/30">
                                                            <TableCell className="font-mono text-xs font-semibold text-slate-600">
                                                                #{inv.id}
                                                            </TableCell>
                                                            <TableCell className="text-xs text-slate-500">
                                                                {inv.created_at}
                                                            </TableCell>
                                                            <TableCell>
                                                                <ul className="list-disc pl-4 space-y-0.5">
                                                                    {inv.items.map((item, idx) => (
                                                                        <li key={idx} className="text-xs text-slate-600">
                                                                            <span className="font-medium">{item.title}</span>
                                                                            {item.qty > 1 && <span className="text-slate-400 text-[10px] ml-1">x{item.qty}</span>}
                                                                        </li>
                                                                    ))}
                                                                </ul>
                                                            </TableCell>
                                                            <TableCell className="font-mono text-xs font-bold text-slate-700 text-right">
                                                                {inv.formatted_unpaid}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <Link
                                                                    href={`/admin/invoices/${inv.id}`}
                                                                    className="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 gap-0.5"
                                                                >
                                                                    {__('general.view') || 'View'}
                                                                    <ExternalLink className="h-3 w-3" />
                                                                </Link>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    )}
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Email Customization Modal */}
            <Dialog open={!!emailModalClient} onOpenChange={() => setEmailModalClient(null)}>
                {emailModalClient && (
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{__('general.send_email_reminder') || 'Send Email Reminder'}</DialogTitle>
                            <DialogDescription>
                                {__('general.customize_email_reminder_desc') || 'Tailor the email subject and body before sending it to the client.'}
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSendEmail} className="space-y-4 my-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="email_recipient">{__('general.recipient') || 'Recipient'}</Label>
                                <Input
                                    id="email_recipient"
                                    type="text"
                                    value={`${emailModalClient.name} <${emailModalClient.email}>`}
                                    disabled
                                    className="bg-slate-50 border-slate-200 text-slate-500 text-sm"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="email_subject">{__('general.custom_email_subject') || 'Email Subject'}</Label>
                                <Input
                                    id="email_subject"
                                    type="text"
                                    value={emailSubject}
                                    onChange={(e) => setEmailSubject(e.target.value)}
                                    placeholder="Enter subject"
                                    required
                                    className="border-slate-200 focus:ring-slate-400 focus:border-slate-400 text-sm"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="email_body">{__('general.custom_email_body') || 'Email Body'}</Label>
                                <Textarea
                                    id="email_body"
                                    rows={8}
                                    value={emailBody}
                                    onChange={(e) => setEmailBody(e.target.value)}
                                    placeholder="Enter message body..."
                                    required
                                    className="border-slate-200 focus:ring-slate-400 focus:border-slate-400 text-sm font-mono"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setEmailModalClient(null)}
                                    disabled={sending}
                                    className="text-xs"
                                >
                                    {__('general.cancel') || 'Cancel'}
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={sending}
                                    className="text-xs bg-slate-900 hover:bg-slate-800 text-white"
                                >
                                    {sending ? (__('general.sending') || 'Sending...') : (__('general.send') || 'Send')}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                )}
            </Dialog>
        </AdminSidebarLayout>
    );
}
