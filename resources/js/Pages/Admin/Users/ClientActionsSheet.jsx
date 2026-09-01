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
    Eye,
    ClipboardEdit,
    LogIn,
    Key,
    ShieldCheck,
    Briefcase,
    Layers,
    Mail,
    Trash2,
    LayoutDashboard,
    ListTodo,
    Folder,
    FileBarChart,
    FileText,
    FileSpreadsheet,
    MessageCircle,
    Users,
    Coins,
    RefreshCw,
    FilePlus,
    Repeat,
    CalendarClock,
    Receipt,
    Wallet,
    Banknote,
    Trophy,
    RotateCcw,
    Shuffle,
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
            <DialogContent className="sm:max-w-4xl p-0 overflow-hidden bg-slate-50 border-slate-200 shadow-2xl rounded-xl flex flex-col max-h-[92dvh]">
                {/* Header */}
                <DialogHeader className="bg-slate-950 p-5 sm:p-6 text-start space-y-0 border-b border-slate-800">
                    <div className="flex items-center justify-between gap-4">
                        <Link 
                            href={`/admin/users/${client.id}`} 
                            onClick={onClose}
                            className="flex items-center gap-3.5 group min-w-0 flex-1 hover:opacity-90 transition-opacity"
                        >
                            <Avatar className="h-11 w-11 bg-white/10 text-white border border-white/10 group-hover:border-white/30 transition-colors shrink-0">
                                <AvatarFallback className="bg-transparent text-base font-bold">
                                    {client.name ? client.name.charAt(0).toUpperCase() : 'U'}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    <DialogTitle className="text-white text-base sm:text-lg font-semibold truncate group-hover:underline underline-offset-2">
                                        {client.name}
                                    </DialogTitle>
                                    {client.role && (
                                        <span className="px-1.5 py-0.5 bg-white/10 text-slate-300 rounded text-[9px] font-semibold uppercase tracking-wider shrink-0">
                                            {client.role}
                                        </span>
                                    )}
                                </div>
                                <DialogDescription className="text-slate-400 text-xs sm:text-sm truncate mt-0.5">
                                    {client.email}
                                </DialogDescription>
                            </div>
                        </Link>
                        <Link
                            href={`/admin/users/${client.id}`}
                            onClick={onClose}
                            className="inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-md transition font-medium shrink-0"
                        >
                            <Eye className="h-3.5 w-3.5" />
                            <span>{__('general.view_profile_1') || 'View profile'}</span>
                        </Link>
                    </div>
                </DialogHeader>

                {/* Content Grid */}
                <div className="p-5 sm:p-6 overflow-y-auto flex-1">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Column 1: Profile & Security */}
                        <div>
                            <h6 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5 px-3">
                                {__('general.profile_and_security') || 'Profile & Security'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link
                                    href={`/admin/users/${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Eye className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.view_profile_1') || 'View profile'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/edit`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ClipboardEdit className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.edit_profile') || 'Edit profile'}</span>
                                </Link>
                                <button
                                    onClick={() => { onClose(); onLoginAs(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <LogIn className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.login_as') || 'Login As'}</span>
                                </button>
                                <button
                                    onClick={() => { onClose(); onResetPassword(client.id); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <Key className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.reset_password') || 'Reset password'}</span>
                                </button>
                                <button
                                    onClick={() => { onClose(); onChangeRole(client); }}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <ShieldCheck className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.change_role') || 'Change role'}</span>
                                </button>
                                <Link
                                    href={`/admin/users/${client.id}#subscriptions`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Layers className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.user_subscriptions') || 'User Subscriptions'}</span>
                                </Link>
                                <button
                                    onClick={handleActivateMembership}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium w-full text-start"
                                >
                                    <Briefcase className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.activate_membership') || 'Activate Membership'}</span>
                                </button>

                                <div className="pt-2 mt-2 border-t border-slate-200" />
                                <Link
                                    href={`/admin/users/${client.id}/emails`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Mail className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.manage_email_aliases') || 'Manage email aliases & merge'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/merge-select`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-red-50 transition-colors text-red-600 font-medium w-full text-start"
                                >
                                    <Trash2 className="h-4 w-4 me-3 shrink-0" />
                                    <span>{__('general.merge_into_another_client') || 'Merge into another client...'}</span>
                                </Link>
                            </div>
                        </div>

                        {/* Column 2: Workspace & Tools */}
                        <div>
                            <h6 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5 px-3">
                                {__('general.workspace_and_tools') || 'Workspace & Tools'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link
                                    href={`/admin/projects?user_id=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <LayoutDashboard className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.projects') || 'Projects'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/tasks/add`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ListTodo className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.assign_task') || 'Set Task'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/files`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Folder className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.files') || 'Files'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/reports`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileBarChart className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.reports') || 'Reports'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/notes`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <ShieldCheck className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.secure_notes') || 'Secure Notes'}</span>
                                </Link>
                                <Link
                                    href={`/admin/users/${client.id}/referrals`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Users className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.manage_referrals') || 'Manage Referrals'}</span>
                                </Link>

                                <div className="pt-2 mt-2 border-t border-slate-200" />
                                <a
                                    href={`/admin/users/${client.id}/balance-sheet`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileSpreadsheet className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.due_balance_sheet') || 'Due Balance Sheet'}</span>
                                </a>
                                <Link
                                    href={`/admin/points_controller/${client.id}/add`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Coins className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.add_points') || 'Add points'}</span>
                                </Link>
                                <button
                                    onClick={handleRecalcBalance}
                                    disabled={recalcLoading}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-amber-50 transition-colors text-amber-800 font-medium w-full text-start disabled:opacity-50"
                                >
                                    <RefreshCw className={`h-4 w-4 me-3 shrink-0 ${recalcLoading ? 'animate-spin' : ''}`} />
                                    <span>{recalcLoading ? __('general.recalculating') || 'Recalculating…' : __('general.recalc_balance') || 'Recalc balance'}</span>
                                </button>
                            </div>
                        </div>

                        {/* Column 3: Finance */}
                        <div>
                            <h6 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5 px-3">
                                {__('general.finance') || 'Finance'}
                            </h6>
                            <div className="flex flex-col space-y-0.5">
                                <Link
                                    href={`/admin/invoices?client_id=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FileText className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.invoices') || 'Invoices'}</span>
                                </Link>
                                <Link
                                    href={`/admin/invoices/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FilePlus className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.new_invoice') || 'New invoice'}</span>
                                </Link>
                                <Link
                                    href={`/admin/business/recurring/invoices/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Repeat className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.create_recurring_invoice') || 'Create a Recurring Invoice'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Receipt className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.all_transactions') || 'All transactions'}</span>
                                </Link>
                                <Link
                                    href={`/admin/payouts/create?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <FilePlus className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.new_payout') || 'New Payout'}</span>
                                </Link>
                                <Link
                                    href={`/admin/business/recurring/salaries?action=create&user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <CalendarClock className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.recurring_payout') || 'Recurring Payout'}</span>
                                </Link>

                                <div className="pt-2 mt-2 border-t border-slate-200" />
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=receive`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Wallet className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.receive_money') || 'Receive Money'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=send-money`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Banknote className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.send_money') || 'Send Money'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=earn`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Trophy className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.earned_money') || 'Earned Money'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=charge`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Banknote className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.charge_account') || 'Charge the account'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions/create?user=${client.id}&type=refund`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <RotateCcw className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.refund_money') || 'Refund Money'}</span>
                                </Link>
                                <Link
                                    href={`/admin/transactions/transfer?user=${client.id}`}
                                    onClick={onClose}
                                    className="flex items-center px-3 py-2 text-sm rounded-md hover:bg-slate-100 transition-colors text-slate-700 font-medium"
                                >
                                    <Shuffle className="h-4 w-4 me-3 text-slate-500 shrink-0" />
                                    <span>{__('general.swap_projects_budget') || 'Swap projects Budget'}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}