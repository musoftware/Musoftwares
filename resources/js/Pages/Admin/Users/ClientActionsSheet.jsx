import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
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
    Briefcase,
    FileBarChart,
    MessageCircle,
    RefreshCw,
    Repeat,
    CalendarClock,
    Trash2,
    Mail,
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ClientActionsSheet({ client, isOpen, onClose, onLoginAs, onResetPassword, onChangeRole, onActivateMembership = null }) {
    const [recalcLoading, setRecalcLoading] = useState(false);

    if (!client) return null;

    const handleRecalcBalance = () => {
        setRecalcLoading(true);
        router.post(`/admin/transactions/recalc-balance/${client.id}`, {}, {
            onSuccess: () => { setRecalcLoading(false); onClose(); },
            onError:   () => { setRecalcLoading(false); },
            onFinish:  () => setRecalcLoading(false),
        });
    };

    const handleActivateMembership = () => {
        onClose();
        if (onActivateMembership) {
            onActivateMembership(client);
        } else {
            router.post(`/admin/users/${client.id}/membership`, {}, { preserveScroll: true });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-3xl p-0 overflow-hidden bg-slate-50 border-slate-200 shadow-2xl rounded-xl flex flex-col max-h-[90dvh]">
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
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Column 1: Profile & Security */}
                        <div>
                            <h6 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3 px-3">
                                {__('general.profile_and_security') || 'Profile & Security'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <button
                                    onClick={() => { onClose(); onLoginAs(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <LogIn className="h-4 w-4 me-3 text-slate-500" />{__('general.login_as')}</button>
                                <Link
                                    href={`/admin/users/${client.id}/edit`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ClipboardEdit className="h-4 w-4 me-3 text-slate-500" />{__('general.edit_profile')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/emails`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Mail className="h-4 w-4 me-3 text-slate-500" />{__('general.manage_emails') || 'إدارة عناوين البريد (Email Aliases)'}</Link>
                                <button
                                    onClick={() => { onClose(); onResetPassword(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <Key className="h-4 w-4 me-3 text-slate-500" />{__('general.reset_password')}</button>
                                <button
                                    onClick={() => { onClose(); onChangeRole(client); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <ShieldCheck className="h-4 w-4 me-3 text-slate-500" />{__('general.change_role')}</button>

                                <div className="pt-3 mt-3 border-t border-slate-200" />
                                <button
                                    onClick={() => {
                                        if (!confirm(__('general.confirm_delete_user') || 'Are you sure you want to delete this user?')) return;
                                        router.delete(`/admin/users/${client.id}`, { preserveScroll: true });
                                        onClose();
                                    }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-red-50 transition-colors text-red-600 font-medium w-full text-start"
                                >
                                    <Trash2 className="h-4 w-4 me-3" />{__('general.delete_user')}</button>
                            </div>
                        </div>

                        {/* Column 2: Workspace & Tools */}
                        <div>
                            <h6 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3 px-3">
                                {__('general.workspace_and_tools') || 'Workspace & Tools'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link
                                    href={`/admin/users/${client.id}/tasks/add`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ListTodo className="h-4 w-4 me-3 text-slate-500" />{__('general.assign_task')}</Link>
                                <Link
                                    href={`/admin/projects?user_id=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <LayoutDashboard className="h-4 w-4 me-3 text-slate-500" />{__('general.projects')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/files`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Folder className="h-4 w-4 me-3 text-slate-500" />{__('general.files')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/notes`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ShieldCheck className="h-4 w-4 me-3 text-slate-500" />{__('general.secure_notes')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/reports`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileBarChart className="h-4 w-4 me-3 text-slate-500" />{__('general.reports')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/referrals`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <MessageCircle className="h-4 w-4 me-3 text-slate-500" />{__('general.manage_referrals')}</Link>
                                <button
                                    onClick={handleActivateMembership}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <Briefcase className="h-4 w-4 me-3 text-slate-500" />{__('general.activate_membership')}</button>

                                <div className="pt-3 mt-3 border-t border-slate-200" />
                                <Link
                                    href={`/admin/users/${client.id}/emails`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Mail className="h-4 w-4 me-3 text-slate-500" />{__('general.manage_email_aliases') || 'Manage email aliases & merge'}</Link>
                                <Link
                                    href={`/admin/users/${client.id}/merge-select`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-red-50 transition-colors text-red-600 font-medium w-full text-start"
                                >
                                    <Trash2 className="h-4 w-4 me-3" />{__('general.merge_into_another_client')}</Link>
                            </div>
                        </div>

                        {/* Column 3: Finance */}
                        <div>
                            <h6 className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3 px-3">
                                {__('general.finance') || 'Finance'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link
                                    href={`/admin/invoices/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FilePlus className="h-4 w-4 me-3 text-slate-500" />{__('general.new_invoice')}</Link>
                                <Link
                                    href={`/admin/business/recurring/invoices/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Repeat className="h-4 w-4 me-3 text-slate-500" />{__('general.create_recurring_invoice')}</Link>
                                <Link
                                    href={`/admin/business/recurring/salaries?action=create&user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <CalendarClock className="h-4 w-4 me-3 text-slate-500" />{__('general.recurring_payout') || 'Recurring Payout'}</Link>
                                <Link
                                    href={`/admin/payouts/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FilePlus className="h-4 w-4 me-3 text-slate-500" />{__('general.new_payout') || 'New Payout'}</Link>
                                <Link
                                    href={`/admin/invoices?client_id=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileText className="h-4 w-4 me-3 text-slate-500" />{__('general.invoices')}</Link>
                                <Link
                                    href={`/admin/transactions?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Receipt className="h-4 w-4 me-3 text-slate-500" />{__('general.all_transactions')}</Link>

                                <div className="pt-3 mt-3 border-t border-slate-200" />
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=receive`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Wallet className="h-4 w-4 me-3 text-slate-500" />{__('general.receive_money')}</Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=send-money`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Banknote className="h-4 w-4 me-3 text-slate-500" />{__('general.send_money')}</Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=earn`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Trophy className="h-4 w-4 me-3 text-slate-500" />{__('general.earned_money')}</Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=charge`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Banknote className="h-4 w-4 me-3 text-slate-500" />{__('general.charge_account')}</Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=refund`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <RotateCcw className="h-4 w-4 me-3 text-slate-500" />{__('general.refund_money')}</Link>
                                <Link
                                    href={`/admin/transactions/transfer?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Shuffle className="h-4 w-4 me-3 text-slate-500" />{__('general.swap_projects_budget')}</Link>
                                <a
                                    href={`/admin/users/${client.id}/balance-sheet`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileText className="h-4 w-4 me-3 text-slate-500" />{__('general.due_balance_sheet')}</a>
                                <button
                                    onClick={handleRecalcBalance}
                                    disabled={recalcLoading}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-yellow-50 transition-colors text-yellow-700 font-medium w-full text-start disabled:opacity-50"
                                >
                                    <RefreshCw className={`h-4 w-4 me-3 ${recalcLoading ? 'animate-spin' : ''}`} />
                                    {recalcLoading ? __('general.recalculating') || 'Recalculating…' : __('general.recalc_balance')}</button>
                                <Link
                                    href={`/admin/points_controller/${client.id}/add`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Coins className="h-4 w-4 me-3 text-slate-500" />{__('general.add_points')}</Link>
                                <Link
                                    href={`/admin/users/${client.id}#subscriptions`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Briefcase className="h-4 w-4 me-3 text-slate-500" />{__('general.user_subscriptions')}</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}