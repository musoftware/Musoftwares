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
import { __ } from '@/lib/i18n';
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
    User
} from 'lucide-react';

export default function ProjectActionsSheet({ project, isOpen, onClose, onEdit }) {
    if (!project) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl p-0 overflow-hidden bg-slate-50 border-slate-200 shadow-2xl rounded-xl">
                <DialogHeader className="bg-slate-950 p-6 text-start space-y-0 border-b border-slate-800">
                    <div className="flex items-center gap-4">
                        <Avatar className="h-12 w-12 bg-white/10 text-white">
                            <AvatarFallback className="bg-transparent text-lg font-bold">
                                {project.project_name ? project.project_name.charAt(0).toUpperCase() : 'P'}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1">
                            <DialogTitle className="text-white text-lg font-semibold truncate">
                                {project.project_name}
                            </DialogTitle>
                            <DialogDescription className="text-slate-400 text-sm truncate">
                                {project.client?.name || 'No Client'}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                    {/* Finance & Billing */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Wallet className="h-4 w-4 text-green-600" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">{__('general.finance_billing')}</h3>
                            <Separator className="flex-1" />
                        </div>
                        
                        <div className="space-y-3">
                            <p className="text-sm font-medium text-slate-600">{__('general.invoices')}</p>
                            <div className="grid grid-cols-2 gap-3">
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/invoices?project_id=${project.id}`}>
                                        <FileText className="h-4 w-4 text-slate-700" /> 
                                        <span className="font-normal text-slate-700">{__('general.all_invoices')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/invoices/create?user=${project.user_id}&project=${project.id}`}>
                                        <FilePlus className="h-4 w-4 text-green-600" /> 
                                        <span className="font-normal text-slate-700">{__('general.create_invoice')}</span>
                                    </Link>
                                </Button>
                            </div>

                            <p className="text-sm font-medium text-slate-600 pt-2">{__('general.timer_transactions')}</p>
                            <div className="grid grid-cols-2 gap-3">
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/create?user=${project.user_id}&project=${project.id}&type=send-money`}>
                                        <Banknote className="h-4 w-4 text-slate-700" /> 
                                        <span className="font-normal text-slate-700">{__('general.send')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/create?user=${project.user_id}&project=${project.id}&type=receive`}>
                                        <Coins className="h-4 w-4 text-slate-700" /> 
                                        <span className="font-normal text-slate-700">{__('general.receive')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/create?user=${project.user_id}&project=${project.id}&type=earned`}>
                                        <Trophy className="h-4 w-4 text-yellow-600" /> 
                                        <span className="font-normal text-slate-700">{__('general.earned')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/create?user=${project.user_id}&project=${project.id}&type=charge`}>
                                        <Receipt className="h-4 w-4 text-slate-500" /> 
                                        <span className="font-normal text-slate-700">{__('general.charge')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/create?user=${project.user_id}&project=${project.id}&type=refund`}>
                                        <RotateCcw className="h-4 w-4 text-slate-500" /> 
                                        <span className="font-normal text-slate-700">{__('general.refund')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions/transfer?user=${project.user_id}&project=${project.id}`}>
                                        <Shuffle className="h-4 w-4 text-slate-700" /> 
                                        <span className="font-normal text-slate-700">{__('general.swap_budgets')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/transactions?user=${project.user_id}&project=${project.id}`}>
                                        <Wallet className="h-4 w-4 text-slate-700" /> 
                                        <span className="font-normal text-slate-700">{__('general.transactions')}</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/users/reports/${project.user_id}`}>
                                        <Download className="h-4 w-4 text-green-600" /> 
                                        <span className="font-normal text-slate-700">{__('general.due_balance_pdf')}</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Workflow */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <LayoutDashboard className="h-4 w-4 text-slate-700" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                {__('general.workflow')}</h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/users/${project.user_id}/projects`}>
                                    <LayoutDashboard className="h-4 w-4 text-slate-700" /> 
                                    <span className="font-normal text-slate-700">{__('general.projects')}</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/users/${project.user_id}/tasks/assign`}>
                                    <ListTodo className="h-4 w-4 text-green-600" /> 
                                    <span className="font-normal text-slate-700">{__('general.assign_tasks')}</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/users/${project.user_id}/notes`}>
                                    <ClipboardEdit className="h-4 w-4 text-yellow-600" /> 
                                    <span className="font-normal text-slate-700">{__('general.notes')}</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}/files`}>
                                    <Folder className="h-4 w-4 text-slate-500" /> 
                                    <span className="font-normal text-slate-700">{__('general.project_files')}</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}/reports`}>
                                    <FileText className="h-4 w-4 text-emerald-600" />
                                    <span className="font-normal text-slate-700">{__('general.progress_reports')}</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}/contracts`}>
                                    <FileText className="h-4 w-4 text-slate-700" /> 
                                    <span className="font-normal text-slate-700">{__('general.contracts_proposals')}</span>
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Management */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-red-600" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                {__('general.management')}</h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}/board`}>
                                    <LayoutDashboard className="h-4 w-4 text-slate-700" />
                                    <span className="font-normal text-slate-700">{__('general.view_project')}</span>
                                </Link>
                            </Button>
                            <Button 
                                variant="outline" 
                                className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm cursor-pointer" 
                                onClick={() => {
                                    onClose();
                                    if (onEdit) onEdit(project);
                                }}
                            >
                                <ClipboardEdit className="h-4 w-4 text-green-600" /> 
                                <span className="font-normal text-slate-700">{__('general.edit_project')}</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

