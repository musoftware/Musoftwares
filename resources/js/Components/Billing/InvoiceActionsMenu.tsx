import { ReactNode } from 'react';
import {
    Bell, Ban, CalendarClock, Check, ChevronDown, Clock, CreditCard, Download, Folder, Layers, Link2,
    ListChecks, Mail, Network, Pause, Play, Plus, Printer, Receipt, Timer, Trash2,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { __ } from '@/lib/i18n';

export interface InvoiceActionsState {
    status: string;
    isSuspended: boolean;
    hasClient: boolean;
    hasProject: boolean;
    canAddTime: boolean;
}

/** Every action the menu can run. The page owns the dialogs and requests; the menu only lists them. */
export interface InvoiceActionHandlers {
    billFromBalance: () => void;
    addPayment: () => void;
    receiveMoney: () => void;
    markPaid: () => void;
    jobStatus: () => void;
    reschedule: () => void;
    addTime: () => void;
    assignProject: () => void;
    toggleSuspend: () => void;
    notifyApp: () => void;
    notifyEmail: () => void;
    share: (duration: string) => void;
    print: () => void;
    downloadPdf: () => void;
    linkedTransactions: () => void;
    cancelInvoice: () => void;
    deleteInvoice: () => void;
}

const SHARE_DURATIONS: Array<{ value: string; label: string }> = [
    { value: '24_hours', label: 'admin.share_24_hours' },
    { value: '3_days', label: 'admin.share_3_days' },
    { value: '1_month', label: 'admin.share_1_month' },
];

/** The invoice page's Actions menu: everything about the invoice itself, in groups, dangerous actions last. */
export default function InvoiceActionsMenu({ state, on }: { state: InvoiceActionsState; on: InvoiceActionHandlers }) {
    const isClosed = state.status === 'cancelled';
    const isOpen = state.status === 'unpaid' || state.status === 'partially_paid';
    const canSend = !isClosed && state.hasClient;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button className="bg-slate-900 hover:bg-slate-900 h-10 px-4 text-white font-semibold">
                    <Layers className="w-4 h-4 me-2" /> {__('admin.actions')} <ChevronDown className="w-4 h-4 ms-2" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64 max-h-[75vh] overflow-y-auto">
                {isOpen && (
                    <Group title={__('admin.invoice_actions_payments')} first>
                        <Item icon={Receipt} label={__('general.bill_from_balance')} onClick={on.billFromBalance} />
                        <Item icon={CreditCard} label={state.status === 'partially_paid' ? __('general.add_payment') : __('general.partial_pay')} onClick={on.addPayment} />
                        <Item icon={Plus} label={__('general.receive_money')} onClick={on.receiveMoney} />
                        <Item icon={Check} label={__('general.mark_as_paid')} onClick={on.markPaid} />
                    </Group>
                )}

                <Group title={__('admin.invoice_actions_invoice')} first={!isOpen}>
                    <Item icon={ListChecks} label={__('general.job_status')} onClick={on.jobStatus} />
                    {isOpen && <Item icon={CalendarClock} label={__('admin.reschedule_invoice')} onClick={on.reschedule} />}
                    {state.canAddTime && <Item icon={Timer} label={__('general.log_time')} onClick={on.addTime} />}
                    {canSend && <Item icon={Folder} label={state.hasProject ? __('general.transfer') : __('admin.assign_to_project')} onClick={on.assignProject} />}
                    {isOpen && (
                        <Item icon={state.isSuspended ? Play : Pause} label={state.isSuspended ? __('admin.unsuspend') : __('admin.suspend')} onClick={on.toggleSuspend} />
                    )}
                </Group>

                <Group title={__('admin.invoice_actions_send')}>
                    {canSend && <Item icon={Bell} label={__('admin.send_fcm_notification')} onClick={on.notifyApp} />}
                    {canSend && <Item icon={Mail} label={__('admin.send_email_notification')} onClick={on.notifyEmail} />}
                    {SHARE_DURATIONS.map((d) => (
                        <Item key={d.value} icon={Clock} label={__(d.label)} onClick={() => on.share(d.value)} />
                    ))}
                    <Item icon={Link2} label={__('general.no_expiry')} onClick={() => on.share('never')} />
                </Group>

                <Group title={__('admin.invoice_actions_files')}>
                    <Item icon={Printer} label={__('general.print')} onClick={on.print} />
                    <Item icon={Download} label={__('general.download_pdf')} onClick={on.downloadPdf} />
                    <Item icon={Network} label={__('admin.view_linked_transactions')} onClick={on.linkedTransactions} />
                </Group>

                <Group title={__('general.danger_zone')}>
                    {!isClosed && <Item icon={Ban} label={__('general.cancel_invoice')} onClick={on.cancelInvoice} danger />}
                    <Item icon={Trash2} label={__('general.delete_invoice')} onClick={on.deleteInvoice} danger />
                </Group>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Group({ title, first = false, children }: { title: string; first?: boolean; children: ReactNode }) {
    return (
        <>
            {!first && <div className="h-px bg-slate-100 my-1" role="separator" />}
            <div className="px-2 pt-1.5 pb-1 text-xs font-bold text-slate-400 uppercase tracking-wider">{title}</div>
            {children}
        </>
    );
}

function Item({ icon: Icon, label, onClick, danger = false }: { icon: React.ElementType; label: string; onClick: () => void; danger?: boolean }) {
    return (
        <DropdownMenuItem onClick={onClick} className={`cursor-pointer ${danger ? 'text-rose-600 focus:text-rose-700 focus:bg-rose-50' : ''}`}>
            <Icon className={`w-4 h-4 me-2 ${danger ? 'text-rose-600' : 'text-slate-500'}`} aria-hidden="true" />
            {label}
        </DropdownMenuItem>
    );
}
