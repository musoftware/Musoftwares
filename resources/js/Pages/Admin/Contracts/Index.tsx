import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { formatMoney } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import {
    Copy,
    Edit,
    FileText,
    History,
    MoreHorizontal,
    Plus,
    Trash2,
} from 'lucide-react';

export default function Index({ contracts }) {
    const handleCopyLink = (uuid: string) => {
        const link = `${window.location.origin}/c/${uuid}`;
        navigator.clipboard.writeText(link);
        alert('Link copied to clipboard');
    };

    const handleDelete = (id: number) => {
        router.delete(`/admin/contracts/${id}`);
    };

    return (
        <AdminSidebarLayout title="Contracts" header="All Contracts">
            <div className="mb-6 flex items-center justify-between">
                <div></div>
                <div className="flex gap-2">
                    <Link href="/admin/contracts/create">
                        <Button className="gap-2">
                            <Plus className="h-4 w-4" />
                            {__('general.create_contract')}
                        </Button>
                    </Link>
                </div>
            </div>

            {contracts.data.length === 0 ? (
                <Card className="border-dashed bg-slate-50 p-12 text-center">
                    <div className="flex flex-col items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50">
                            <FileText className="h-6 w-6 text-slate-900" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">
                                {__('general.no_contracts_yet')}
                            </h3>
                            <p className="mt-1 max-w-sm text-slate-500">
                                {__(
                                    'general.create_a_contract_or_proposal_for_this_p',
                                )}
                            </p>
                        </div>
                        <Link href="/admin/contracts/create">
                            <Button className="mt-2 gap-2">
                                <Plus className="h-4 w-4" />
                                {__('general.create_contract')}
                            </Button>
                        </Link>
                    </div>
                </Card>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {contracts.data.map((contract: any) => (
                        <Card key={contract.id} className="flex flex-col">
                            <CardHeader className="border-b pb-3">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-2">
                                        <FileText className="h-5 w-5 text-slate-900" />
                                        <CardTitle className="truncate text-base">
                                            {contract.project_name}
                                        </CardTitle>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${contract.status === 'signed' ? 'bg-green-100 text-slate-900' : ''} ${contract.status === 'draft' ? 'bg-slate-100 text-slate-700' : ''} ${contract.status === 'sent' ? 'bg-slate-50 text-slate-900' : ''} `}
                                        >
                                            {contract.status
                                                .charAt(0)
                                                .toUpperCase() +
                                                contract.status.slice(1)}
                                        </span>
                                        <AlertDialog>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/admin/contracts/${contract.id}/edit`}
                                                            className="flex w-full cursor-pointer items-center"
                                                        >
                                                            <Edit className="mr-2 h-4 w-4" />
                                                            {__('general.edit')}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            handleCopyLink(
                                                                contract.uuid,
                                                            )
                                                        }
                                                        className="cursor-pointer"
                                                    >
                                                        <Copy className="mr-2 h-4 w-4" />
                                                        {__(
                                                            'general.copy_public_link',
                                                        )}
                                                    </DropdownMenuItem>
                                                    <AlertDialogTrigger
                                                        render={
                                                            <DropdownMenuItem
                                                                className="cursor-pointer text-red-600 focus:text-red-600"
                                                                onSelect={(e) => e.preventDefault()}
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                {__('general.delete')}
                                                            </DropdownMenuItem>
                                                        }
                                                    />
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        Are you sure?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This action cannot be
                                                        undone. This will
                                                        permanently delete the
                                                        contract.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>
                                                        Cancel
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction
                                                        onClick={() =>
                                                            handleDelete(
                                                                contract.id,
                                                            )
                                                        }
                                                        className="bg-red-600 hover:bg-red-700"
                                                    >
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </div>
                                <CardDescription className="mt-2 line-clamp-2 text-xs">
                                    {contract.description ||
                                        'No description provided.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-1 flex-col justify-between gap-4 pt-4">
                                <div>
                                    <div className="mb-2 flex justify-between text-sm">
                                        <span className="text-slate-500">
                                            Amount:
                                        </span>
                                        <span className="font-medium text-slate-900">
                                            {formatMoney(
                                                contract.total_amount,
                                                contract.currency_id,
                                            )}
                                        </span>
                                    </div>
                                    <div className="mb-2 flex justify-between text-sm">
                                        <span className="text-slate-500">
                                            Versions:
                                        </span>
                                        <span className="flex items-center gap-1 font-medium">
                                            <History className="h-3 w-3" />
                                            {contract.versions?.length || 1}
                                        </span>
                                    </div>
                                    {contract.status === 'signed' && (
                                        <div className="mb-2 flex justify-between text-sm">
                                            <span className="text-slate-500">
                                                Signed by:
                                            </span>
                                            <span
                                                className="max-w-[120px] truncate font-semibold text-slate-900"
                                                title={contract.client_name}
                                            >
                                                {contract.client_name}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AdminSidebarLayout>
    );
}
