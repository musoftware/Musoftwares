import React from 'react';
import { Link } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Separator } from '@/Components/ui/separator';
import {
    Wallet,
    FileText,
    FilePlus,
    Banknote,
    Coins,
    Trophy,
    Receipt,
    RotateCcw,
    Shuffle,
    Download,
    LayoutDashboard,
    ListTodo,
    ClipboardEdit,
    Folder,
    User,
    LogIn,
    Key,
    ShieldCheck,
    Briefcase
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ClientActionsSheet({ client, isOpen, onClose, onLoginAs, onResetPassword, onChangeRole }) {
    if (!client) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl p-0 overflow-hidden bg-slate-50 border-slate-200 shadow-2xl rounded-xl flex flex-col max-h-[90dvh]">
                <DialogHeader className="bg-slate-950 p-6 text-start space-y-0 border-b border-slate-800">
                    <div className="flex items-center gap-4">
                        <Avatar className="h-12 w-12 bg-white/10 text-white">
                            <AvatarFallback className="bg-transparent text-lg font-bold">
                                {client.name ? client.name.charAt(0).toUpperCase() : 'U'}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1">
                            <DialogTitle className="text-white text-lg font-semibold truncate">
                                {client.name}
                            </DialogTitle>
                            <DialogDescription className="text-slate-400 text-sm truncate">
                                {client.email}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="p-6 overflow-y-auto flex-1">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Finance & Billing Column */}
                        <div>
                            <h6 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3 px-3">
                                Finance
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link 
                                    href={`/admin/invoices/create?user=${client.id}`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FilePlus className="h-4 w-4 me-3 text-slate-500" />{__('general.new_invoice')}</Link>
                                <Link 
                                    href={`/admin/transactions/create?user=${client.id}&type=receive`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Wallet className="h-4 w-4 me-3 text-slate-500" />{__('general.receive_money')}</Link>
                                <Link 
                                    href={`/admin/points_controller/${client.id}/add`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Coins className="h-4 w-4 me-3 text-slate-500" />{__('general.add_points')}</Link>
                                <Link 
                                    href={`/admin/transactions/create?user=${client.id}&type=send-money`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Banknote className="h-4 w-4 me-3 text-slate-500" />{__('general.send_money')}</Link>
                                <Link 
                                    href={`/admin/transactions/create?user=${client.id}&type=refund`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <RotateCcw className="h-4 w-4 me-3 text-slate-500" />{__('general.refund_money')}</Link>
                                <Link 
                                    href={`/admin/invoices?client_id=${client.id}`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileText className="h-4 w-4 me-3 text-slate-500" /> Invoices
                                </Link>

                                <Link 
                                    href={`/admin/transactions?user=${client.id}`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Receipt className="h-4 w-4 me-3 text-slate-500" />{__('general.all_transactions')}</Link>
                                <button 
                                    onClick={() => { onClose(); onResetPassword(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <Key className="h-4 w-4 me-3 text-slate-500" /> {__("general.reset_password")}
                                </button>
                                <button 
                                    onClick={() => { onClose(); onChangeRole(client); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <ShieldCheck className="h-4 w-4 me-3 text-slate-500" /> {__("general.change_role")}
                                </button>
                            </div>
                        </div>

                        {/* Account & Tools Column */}
                        <div>
                            <h6 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3 px-3">{__('general.account_tools')}</h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link 
                                    href={`/admin/projects?user_id=${client.id}`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <LayoutDashboard className="h-4 w-4 me-3 text-slate-500" /> Projects
                                </Link>
                                <Link 
                                    href={`/admin/users/${client.id}/tasks/add`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ListTodo className="h-4 w-4 me-3 text-slate-500" />{__('general.assign_task')}</Link>
                                <Link 
                                    href={`/admin/users/${client.id}/notes`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ClipboardEdit className="h-4 w-4 me-3 text-slate-500" /> Notes
                                </Link>
                                <Link 
                                    href={`/admin/users/${client.id}/files`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Folder className="h-4 w-4 me-3 text-slate-500" /> Files
                                </Link>
                                <Link 
                                    href={`/admin/users/${client.id}`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <User className="h-4 w-4 me-3 text-slate-500" />{__('general.view_profile_1')}</Link>
                                <Link 
                                    href={`/admin/users/${client.id}/edit`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ClipboardEdit className="h-4 w-4 me-3 text-slate-500" />{__('general.edit_profile')}
                                </Link>
                                <Link 
                                    href={`/admin/users/${client.id}#subscriptions`} 
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Briefcase className="h-4 w-4 me-3 text-slate-500" />{__('general.user_subscriptions')}
                                </Link>
                                <button 
                                    onClick={() => { onClose(); onLoginAs(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <LogIn className="h-4 w-4 me-3 text-slate-500" />{__('general.login_as')}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
