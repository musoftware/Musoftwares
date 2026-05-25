import React from 'react';
import { Link } from '@inertiajs/react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
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

export default function ProjectActionsSheet({ project, isOpen, onClose }) {
    if (!project) return null;

    return (
        <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full sm:max-w-md overflow-y-auto p-0 flex flex-col bg-slate-50/50">
                <SheetHeader className="bg-slate-950 p-6 text-left space-y-0 border-b-0 shadow-none">
                    <div className="flex items-center gap-4">
                        <Avatar className="h-12 w-12 bg-white/10 text-white">
                            <AvatarFallback className="bg-transparent text-lg font-bold">
                                {project.project_name ? project.project_name.charAt(0).toUpperCase() : 'P'}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1">
                            <SheetTitle className="text-white text-lg font-semibold truncate">
                                {project.project_name}
                            </SheetTitle>
                            <SheetDescription className="text-slate-400 text-sm truncate">
                                {project.client?.name || 'No Client'}
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div className="p-6 space-y-8 flex-1">
                    {/* Finance & Billing */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Wallet className="h-4 w-4 text-emerald-500" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Finance & Billing
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        
                        <div className="space-y-3">
                            <p className="text-sm font-medium text-slate-600">Invoices</p>
                            <div className="grid grid-cols-2 gap-3">
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href={`/admin/invoices?search=${encodeURIComponent(project.project_name)}`}>
                                        <FileText className="h-4 w-4 text-blue-500" /> 
                                        <span className="font-normal text-slate-700">All Invoices</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="/admin/invoices/create">
                                        <FilePlus className="h-4 w-4 text-emerald-500" /> 
                                        <span className="font-normal text-slate-700">Create Invoice</span>
                                    </Link>
                                </Button>
                            </div>

                            <p className="text-sm font-medium text-slate-600 pt-2">Timer Transactions</p>
                            <div className="grid grid-cols-2 gap-3">
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Banknote className="h-4 w-4 text-sky-500" /> 
                                        <span className="font-normal text-slate-700">Send</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Coins className="h-4 w-4 text-sky-500" /> 
                                        <span className="font-normal text-slate-700">Receive</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Trophy className="h-4 w-4 text-amber-500" /> 
                                        <span className="font-normal text-slate-700">Earned</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Receipt className="h-4 w-4 text-slate-500" /> 
                                        <span className="font-normal text-slate-700">Charge</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <RotateCcw className="h-4 w-4 text-slate-500" /> 
                                        <span className="font-normal text-slate-700">Refund</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Shuffle className="h-4 w-4 text-blue-500" /> 
                                        <span className="font-normal text-slate-700">Swap Budgets</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Wallet className="h-4 w-4 text-blue-500" /> 
                                        <span className="font-normal text-slate-700">Transactions</span>
                                    </Link>
                                </Button>
                                <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                    <Link href="#">
                                        <Download className="h-4 w-4 text-emerald-500" /> 
                                        <span className="font-normal text-slate-700">Due Balance PDF</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Workflow */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <LayoutDashboard className="h-4 w-4 text-sky-500" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Workflow
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href="#">
                                    <LayoutDashboard className="h-4 w-4 text-blue-500" /> 
                                    <span className="font-normal text-slate-700">Projects</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href="#">
                                    <ListTodo className="h-4 w-4 text-emerald-500" /> 
                                    <span className="font-normal text-slate-700">Assign Tasks</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href="#">
                                    <ClipboardEdit className="h-4 w-4 text-amber-500" /> 
                                    <span className="font-normal text-slate-700">Notes</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}/files`}>
                                    <Folder className="h-4 w-4 text-slate-500" /> 
                                    <span className="font-normal text-slate-700">Project Files</span>
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Management */}
                    <div className="space-y-4 pt-2">
                        <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-rose-500" />
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Management
                            </h3>
                            <Separator className="flex-1" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href={`/admin/projects/${project.id}`}>
                                    <LayoutDashboard className="h-4 w-4 text-blue-500" /> 
                                    <span className="font-normal text-slate-700">View Project</span>
                                </Link>
                            </Button>
                            <Button variant="outline" className="flex-col items-start h-auto py-3 px-4 gap-2 bg-white hover:bg-slate-50 shadow-sm" asChild>
                                <Link href="#">
                                    <ClipboardEdit className="h-4 w-4 text-emerald-500" /> 
                                    <span className="font-normal text-slate-700">Edit Project</span>
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
