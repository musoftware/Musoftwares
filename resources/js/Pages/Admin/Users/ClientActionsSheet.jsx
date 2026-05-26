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
    Key
} from 'lucide-react';

export default function ClientActionsSheet({ client, isOpen, onClose, onLoginAs, onResetPassword }) {
    if (!client) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl p-0 overflow-hidden bg-slate-50 border-slate-200 shadow-2xl rounded-xl">
                <DialogHeader className="bg-slate-950 p-6 text-left space-y-0 border-b border-slate-800">
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

                <div className="p-6 space-y-6 max-h-[85vh] overflow-y-auto">
                    {/* Finance & Billing */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Wallet className="h-4 w-4 text-slate-800" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Finance & Billing
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/invoices/create?user=${client.id}`}>
                                    <FilePlus className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">New Invoice</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/transactions/create?user=${client.id}&type=receive`}>
                                    <Coins className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Receive Money</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/transactions/create?user=${client.id}&type=send-money`}>
                                    <Banknote className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Send Money</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/transactions/create?user=${client.id}&type=refund`}>
                                    <RotateCcw className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Refund Money</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/invoices?user=${client.id}`}>
                                    <FileText className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Invoices</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/transactions/transfer?user=${client.id}`}>
                                    <Shuffle className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Swap Budgets</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/transactions?user=${client.id}`}>
                                    <Receipt className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">All Transactions</span>
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Workflow */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <LayoutDashboard className="h-4 w-4 text-slate-800" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Workflow
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}/projects`}>
                                    <LayoutDashboard className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Projects</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}/tasks/assign`}>
                                    <ListTodo className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Assign Tasks</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}/notes`}>
                                    <ClipboardEdit className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Notes</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}/files`}>
                                    <Folder className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">User Files</span>
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Account */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-slate-800" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Account
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}`}>
                                    <User className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">View Profile</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" asChild>
                                <Link href={`/admin/users/${client.id}/edit`}>
                                    <ClipboardEdit className="h-4 w-4 text-slate-800" /> 
                                    <span className="font-normal text-slate-700">Edit Client</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" onClick={() => onLoginAs(client.id)}>
                                <LogIn className="h-4 w-4 text-slate-800" /> 
                                <span className="font-normal text-slate-700">Login As</span>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 hover:text-black hover:border-slate-300 transition-colors shadow-sm" onClick={() => onResetPassword(client.id)}>
                                <Key className="h-4 w-4 text-slate-800" /> 
                                <span className="font-normal text-slate-700">Reset Password</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
