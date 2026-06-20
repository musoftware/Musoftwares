import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { 
    FileText, ArrowDownLeft, ArrowUpRight, RotateCcw, 
    Receipt, Wallet, Eye, Edit2, Trash2,
    StickyNote, File, Briefcase, MessageSquare, CheckSquare 
} from 'lucide-react';

interface ClientActionModalProps {
    client: any | null;
    isOpen: boolean;
    onClose: () => void;
    onDelete: (client: any) => void;
    auth?: any;
}

export function ClientActionModal({ client, isOpen, onClose, onDelete, auth }: ClientActionModalProps) {
    if (!client) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-3xl flex flex-col max-h-[90dvh]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-3">
                        <div className="flex items-center justify-center w-9 h-9 rounded-full bg-slate-100 text-slate-700 text-sm font-bold">
                            {client?.name?.substring(0, 2)?.toUpperCase()}
                        </div>
                        <div>
                            <span className="block">{client?.name}</span>
                            <span className="text-xs font-normal text-slate-400">{client?.email}</span>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-2 overflow-y-auto flex-1">
                    {/* Finance Column */}
                    <div>
                        <h4 className="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-3 px-1">{__('general.finance')}</h4>
                        <div className="space-y-0.5">
                            {[
                                { icon: FileText, label: __('general.new_invoice'), color: 'text-slate-600', href: route('erp.invoices.create') + '?client_id=' + client.id },
                                { icon: ArrowDownLeft, label: __('general.receive_money'), color: 'text-emerald-600', href: route('erp.clients.wallet.adjust', client.id) + '?type=credit' },
                                { icon: ArrowUpRight, label: __('general.send_money'), color: 'text-amber-600', href: route('erp.clients.wallet.adjust', client.id) + '?type=debit' },
                                { icon: RotateCcw, label: __('general.refund'), color: 'text-blue-600', href: route('erp.clients.wallet.adjust', client.id) + '?type=refund' },
                                { icon: Receipt, label: __('general.all_invoices'), color: 'text-slate-600', href: route('erp.invoices.index') + '?search=' + encodeURIComponent(client.name) },
                                { icon: Wallet, label: __('general.transactions'), color: 'text-slate-600', href: route('erp.clients.wallet.index', client.id) },
                            ].map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-50 transition-colors group"
                                    onClick={onClose}
                                >
                                    <item.icon className={`h-4 w-4 ${item.color} shrink-0`} />
                                    <span className="group-hover:translate-x-0.5 transition-transform">{item.label}</span>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Workspace Column */}
                    <div>
                        <h4 className="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-3 px-1">{__('general.workspace')}</h4>
                        <div className="space-y-0.5">
                            {[
                                { icon: StickyNote, label: __('general.notes'), color: 'text-amber-600', href: route('erp.clients.show', client.id) + '#notes' },
                                { icon: File, label: __('general.files'), color: 'text-blue-600', href: route('erp.clients.show', client.id) + '#files' },
                                ...(auth?.erp_addons?.includes('erp-projects') ? [
                                    { icon: Briefcase, label: __('general.projects'), color: 'text-indigo-600', href: route('erp.projects.index') + '?client_id=' + client.id }
                                ] : []),
                                ...(auth?.erp_addons?.includes('erp-tasks') ? [
                                    { icon: CheckSquare, label: __('general.tasks'), color: 'text-green-600', href: route('erp.tasks.index') + '?client_id=' + client.id }
                                ] : []),
                                ...(auth?.erp_addons?.includes('erp-tickets') ? [
                                    { icon: MessageSquare, label: __('general.tickets'), color: 'text-purple-600', href: route('erp.tickets.index') + '?client_id=' + client.id }
                                ] : [])
                            ].map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-50 transition-colors group"
                                    onClick={onClose}
                                >
                                    <item.icon className={`h-4 w-4 ${item.color} shrink-0`} />
                                    <span className="group-hover:translate-x-0.5 transition-transform">{item.label}</span>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Account Column */}
                    <div>
                        <h4 className="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-3 px-1">{__('general.account')}</h4>
                        <div className="space-y-0.5">
                            {[
                                { icon: Eye, label: __('general.view_profile'), color: 'text-slate-600', href: route('erp.clients.show', client.id) },
                                { icon: Edit2, label: __('general.edit_client'), color: 'text-slate-600', href: route('erp.clients.edit', client.id) },
                            ].map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-50 transition-colors group"
                                    onClick={onClose}
                                >
                                    <item.icon className={`h-4 w-4 ${item.color} shrink-0`} />
                                    <span className="group-hover:translate-x-0.5 transition-transform">{item.label}</span>
                                </Link>
                            ))}
                            <div className="border-t border-slate-100 my-2" />
                            <button
                                onClick={() => {
                                    onClose();
                                    onDelete(client);
                                }}
                                className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-rose-600 hover:bg-rose-50 transition-colors group w-full text-left"
                            >
                                <Trash2 className="h-4 w-4 shrink-0" />
                                <span className="group-hover:translate-x-0.5 transition-transform">{__('general.delete_client')}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
