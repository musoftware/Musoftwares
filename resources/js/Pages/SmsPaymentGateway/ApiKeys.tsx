import { __ } from '@/lib/i18n';
import React, { useState, useEffect } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from '@/Components/ui/table';
import {
    ArrowLeft,
    Plus,
    Key,
    Copy,
    Check,
    MoreHorizontal,
    AlertTriangle,
    RefreshCw,
    Trash2,
    ShieldAlert,
    Eye,
    EyeOff,
} from 'lucide-react';

interface ApiKey {
    id: number;
    name: string;
    publishable_key: string;
    secret_key_last_four: string;
    is_test: boolean;
    is_active: boolean;
    last_used_at: string | null;
    created_at: string;
}

interface Props {
    apiKeys: ApiKey[];
}

interface FlashData {
    new_publishable_key?: string;
    new_secret_key?: string;
    success?: string;
    error?: string;
}

export default function ApiKeys({ apiKeys }: Props) {
    const { flash } = usePage<any>().props;

    // Create key dialog
    const [createOpen, setCreateOpen] = useState(false);
    const createForm = useForm({
        name: '',
        is_test: true,
    });

    // Post-creation reveal dialog
    const [revealOpen, setRevealOpen] = useState(false);
    const [revealedPublishableKey, setRevealedPublishableKey] = useState('');
    const [revealedSecretKey, setRevealedSecretKey] = useState('');

    // Action menu dialog
    const [actionOpen, setActionOpen] = useState(false);
    const [actionKey, setActionKey] = useState<ApiKey | null>(null);

    // Roll confirmation dialog
    const [rollConfirmOpen, setRollConfirmOpen] = useState(false);
    const [rolling, setRolling] = useState(false);

    // Delete confirmation dialog
    const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    // Copy feedback state
    const [copiedField, setCopiedField] = useState<string | null>(null);

    // When new keys are flashed after creation, show the reveal dialog
    useEffect(() => {
        if (flash?.new_publishable_key && flash?.new_secret_key) {
            setRevealedPublishableKey(flash.new_publishable_key);
            setRevealedSecretKey(flash.new_secret_key);
            setRevealOpen(true);
            setCreateOpen(false);
        }
    }, [flash?.new_publishable_key, flash?.new_secret_key]);

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('sms-payment-gateway.api-keys.store'), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
            },
        });
    };

    const handleCopy = (text: string, fieldId: string) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopiedField(fieldId);
            setTimeout(() => setCopiedField(null), 2000);
        });
    };

    const handleRollSecret = () => {
        if (!actionKey) return;
        setRolling(true);
        router.post(route('sms-payment-gateway.api-keys.roll', actionKey.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                setRolling(false);
                setRollConfirmOpen(false);
                setActionOpen(false);
                setActionKey(null);
            },
        });
    };

    const handleDelete = () => {
        if (!actionKey) return;
        setDeleting(true);
        router.delete(route('sms-payment-gateway.api-keys.delete', actionKey.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteConfirmOpen(false);
                setActionOpen(false);
                setActionKey(null);
            },
        });
    };

    const openActionMenu = (key: ApiKey) => {
        setActionKey(key);
        setActionOpen(true);
    };

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('API Keys - Payment Gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Key className="w-6 h-6 text-indigo-600" />
                                {__('API Keys')}
                            </h1>
                            <p className="text-slate-500 mt-1">
                                {__('general.manage_your_publishable_and_secret_keys_for_api_integration')}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {/* Create Key Button */}
                            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                                <DialogTrigger render={
                                    <Button className="bg-indigo-600 hover:bg-indigo-700 text-white" />
                                }>
                                    <Plus className="w-4 h-4 mr-2" />
                                    {__('Create Key')}
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>{__('Create API Key')}</DialogTitle>
                                        <DialogDescription>
                                            {__('general.generate_a_new_pair_of_publishable_and_secret_keys_for_your_integration')}
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form onSubmit={handleCreateSubmit} className="space-y-5">
                                        <div className="space-y-2">
                                            <Label htmlFor="key-name">{__('Key Name')}</Label>
                                            <Input
                                                id="key-name"
                                                type="text"
                                                placeholder={__('general.e_g_production_server_mobile_app')}
                                                value={createForm.data.name}
                                                onChange={e => createForm.setData('name', e.target.value)}
                                                required
                                            />
                                            {createForm.errors.name && (
                                                <p className="text-sm text-red-600">{createForm.errors.name}</p>
                                            )}
                                        </div>

                                        <div className="flex items-center justify-between p-4 border rounded-lg bg-slate-50/50">
                                            <div className="space-y-0.5">
                                                <Label className="text-base">{__('Test Mode')}</Label>
                                                <p className="text-sm text-slate-500">
                                                    {createForm.data.is_test
                                                        ? __('general.this_key_will_only_work_in_test_sandbox_environment')
                                                        : __('general.this_key_will_process_real_payments_in_production')
                                                    }
                                                </p>
                                            </div>
                                            <Switch
                                                checked={createForm.data.is_test}
                                                onCheckedChange={(checked) => createForm.setData('is_test', checked)}
                                            />
                                        </div>

                                        <DialogFooter>
                                            <Button
                                                type="submit"
                                                disabled={createForm.processing}
                                                className="w-full bg-indigo-600 hover:bg-indigo-700 text-white"
                                            >
                                                {createForm.processing ? (
                                                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                ) : (
                                                    <Key className="w-4 h-4 mr-2" />
                                                )}
                                                {__('Generate Keys')}
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>

                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                {__('Back')}
                            </Button>
                        </div>
                    </div>

                    {/* Post-Creation Key Reveal Dialog */}
                    <Dialog open={revealOpen} onOpenChange={setRevealOpen}>
                        <DialogContent className="sm:max-w-lg" showCloseButton={false}>
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <ShieldAlert className="w-5 h-5 text-amber-500" />
                                    {__('Your API Keys')}
                                </DialogTitle>
                                <DialogDescription>
                                    {__('general.copy_your_keys_now_the_secret_key_will_only_be_shown_once')}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                {/* Warning Banner */}
                                <div className="flex items-start gap-3 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">
                                    <AlertTriangle className="w-5 h-5 mt-0.5 shrink-0" />
                                    <div className="text-sm">
                                        <p className="font-semibold">{__('Important')}</p>
                                        <p>{__('general.the_secret_key_will_only_be_shown_once_save_it_securely_you_will_not_be_able_to_see_it_again')}</p>
                                    </div>
                                </div>

                                {/* Publishable Key */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs uppercase tracking-wide text-slate-500">
                                        {__('Publishable Key')}
                                    </Label>
                                    <div className="flex items-center gap-2">
                                        <div className="flex-1 rounded-lg bg-slate-900 text-emerald-400 px-4 py-3 font-mono text-sm break-all select-all">
                                            {revealedPublishableKey}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="icon-sm"
                                            onClick={() => handleCopy(revealedPublishableKey, 'reveal-pk')}
                                            className="shrink-0"
                                        >
                                            {copiedField === 'reveal-pk' ? (
                                                <Check className="w-4 h-4 text-emerald-500" />
                                            ) : (
                                                <Copy className="w-4 h-4" />
                                            )}
                                        </Button>
                                    </div>
                                </div>

                                {/* Secret Key */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs uppercase tracking-wide text-slate-500">
                                        {__('Secret Key')}
                                    </Label>
                                    <div className="flex items-center gap-2">
                                        <div className="flex-1 rounded-lg bg-slate-900 text-rose-400 px-4 py-3 font-mono text-sm break-all select-all">
                                            {revealedSecretKey}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="icon-sm"
                                            onClick={() => handleCopy(revealedSecretKey, 'reveal-sk')}
                                            className="shrink-0"
                                        >
                                            {copiedField === 'reveal-sk' ? (
                                                <Check className="w-4 h-4 text-emerald-500" />
                                            ) : (
                                                <Copy className="w-4 h-4" />
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    onClick={() => setRevealOpen(false)}
                                    className="w-full"
                                >
                                    {__('I have saved my keys')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    {/* API Keys List */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{__('Active Keys')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {apiKeys.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-16 px-6 text-center">
                                    <div className="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                        <Key className="w-7 h-7 text-slate-400" />
                                    </div>
                                    <h3 className="text-base font-semibold text-slate-800 mb-1">
                                        {__('No API keys yet')}
                                    </h3>
                                    <p className="text-sm text-slate-500 max-w-sm">
                                        {__('general.create_your_first_api_key_to_start_integrating_the_payment_gateway_into_your_application')}
                                    </p>
                                </div>
                            ) : (
                                <>
                                    {/* Desktop Table */}
                                    <div className="hidden md:block">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>{__('Name')}</TableHead>
                                                    <TableHead>{__('Type')}</TableHead>
                                                    <TableHead>{__('Publishable Key')}</TableHead>
                                                    <TableHead>{__('Secret Key')}</TableHead>
                                                    <TableHead>{__('Last Used')}</TableHead>
                                                    <TableHead>{__('Created')}</TableHead>
                                                    <TableHead className="w-10"></TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {apiKeys.map((key) => (
                                                    <TableRow key={key.id}>
                                                        <TableCell className="font-medium">
                                                            {key.name}
                                                        </TableCell>
                                                        <TableCell>
                                                            {key.is_test ? (
                                                                <Badge className="bg-amber-100 text-amber-800 border-amber-200">
                                                                    🟡 {__('Test')}
                                                                </Badge>
                                                            ) : (
                                                                <Badge className="bg-emerald-100 text-emerald-800 border-emerald-200">
                                                                    🟢 {__('Live')}
                                                                </Badge>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-1.5">
                                                                <code className="text-xs bg-slate-100 px-2 py-1 rounded font-mono text-slate-700 max-w-[180px] truncate">
                                                                    {key.publishable_key}
                                                                </code>
                                                                <button
                                                                    onClick={() => handleCopy(key.publishable_key, `pk-${key.id}`)}
                                                                    className="p-1 rounded hover:bg-slate-100 transition-colors"
                                                                    title={__('Copy')}
                                                                >
                                                                    {copiedField === `pk-${key.id}` ? (
                                                                        <Check className="w-3.5 h-3.5 text-emerald-500" />
                                                                    ) : (
                                                                        <Copy className="w-3.5 h-3.5 text-slate-400" />
                                                                    )}
                                                                </button>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <code className="text-xs bg-slate-100 px-2 py-1 rounded font-mono text-slate-500">
                                                                sk_{'••••'}{key.secret_key_last_four}
                                                            </code>
                                                        </TableCell>
                                                        <TableCell className="text-sm text-slate-500">
                                                            {formatDate(key.last_used_at)}
                                                        </TableCell>
                                                        <TableCell className="text-sm text-slate-500">
                                                            {formatDate(key.created_at)}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Dialog open={actionOpen && actionKey?.id === key.id} onOpenChange={(open) => {
                                                                if (!open) {
                                                                    setActionOpen(false);
                                                                    setActionKey(null);
                                                                }
                                                            }}>
                                                                <DialogTrigger render={
                                                                    <Button
                                                                        variant="ghost"
                                                                        className="h-8 w-8 p-0"
                                                                        onClick={() => openActionMenu(key)}
                                                                    />
                                                                }>
                                                                    <span className="sr-only">{__('Open menu')}</span>
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </DialogTrigger>
                                                                <DialogContent className="sm:max-w-xs">
                                                                    <DialogHeader>
                                                                        <DialogTitle>{__('Actions')}</DialogTitle>
                                                                        <DialogDescription>
                                                                            {key.name}
                                                                        </DialogDescription>
                                                                    </DialogHeader>
                                                                    <div className="flex flex-col gap-2 py-2">
                                                                        <Button
                                                                            variant="outline"
                                                                            className="justify-start"
                                                                            onClick={() => {
                                                                                setActionOpen(false);
                                                                                setRollConfirmOpen(true);
                                                                            }}
                                                                        >
                                                                            <RefreshCw className="w-4 h-4 mr-2" />
                                                                            {__('Roll Secret Key')}
                                                                        </Button>
                                                                        <Button
                                                                            variant="destructive"
                                                                            className="justify-start"
                                                                            onClick={() => {
                                                                                setActionOpen(false);
                                                                                setDeleteConfirmOpen(true);
                                                                            }}
                                                                        >
                                                                            <Trash2 className="w-4 h-4 mr-2" />
                                                                            {__('Delete Key')}
                                                                        </Button>
                                                                    </div>
                                                                </DialogContent>
                                                            </Dialog>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    {/* Mobile Card View */}
                                    <div className="md:hidden divide-y">
                                        {apiKeys.map((key) => (
                                            <div key={key.id} className="p-4 space-y-3">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-slate-900">{key.name}</span>
                                                        {key.is_test ? (
                                                            <Badge className="bg-amber-100 text-amber-800 border-amber-200 text-[10px]">
                                                                🟡 {__('Test')}
                                                            </Badge>
                                                        ) : (
                                                            <Badge className="bg-emerald-100 text-emerald-800 border-emerald-200 text-[10px]">
                                                                🟢 {__('Live')}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <Dialog open={actionOpen && actionKey?.id === key.id} onOpenChange={(open) => {
                                                        if (!open) {
                                                            setActionOpen(false);
                                                            setActionKey(null);
                                                        }
                                                    }}>
                                                        <DialogTrigger render={
                                                            <Button
                                                                variant="ghost"
                                                                className="h-8 w-8 p-0"
                                                                onClick={() => openActionMenu(key)}
                                                            />
                                                        }>
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </DialogTrigger>
                                                        <DialogContent className="sm:max-w-xs">
                                                            <DialogHeader>
                                                                <DialogTitle>{__('Actions')}</DialogTitle>
                                                                <DialogDescription>
                                                                    {key.name}
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <div className="flex flex-col gap-2 py-2">
                                                                <Button
                                                                    variant="outline"
                                                                    className="justify-start"
                                                                    onClick={() => {
                                                                        setActionOpen(false);
                                                                        setRollConfirmOpen(true);
                                                                    }}
                                                                >
                                                                    <RefreshCw className="w-4 h-4 mr-2" />
                                                                    {__('Roll Secret Key')}
                                                                </Button>
                                                                <Button
                                                                    variant="destructive"
                                                                    className="justify-start"
                                                                    onClick={() => {
                                                                        setActionOpen(false);
                                                                        setDeleteConfirmOpen(true);
                                                                    }}
                                                                >
                                                                    <Trash2 className="w-4 h-4 mr-2" />
                                                                    {__('Delete Key')}
                                                                </Button>
                                                            </div>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>

                                                {/* Publishable Key */}
                                                <div className="space-y-1">
                                                    <span className="text-xs text-slate-500 uppercase tracking-wide">{__('Publishable Key')}</span>
                                                    <div className="flex items-center gap-1.5">
                                                        <code className="text-xs bg-slate-100 px-2 py-1.5 rounded font-mono text-slate-700 flex-1 truncate" dir="ltr">
                                                            {key.publishable_key}
                                                        </code>
                                                        <button
                                                            onClick={() => handleCopy(key.publishable_key, `pk-m-${key.id}`)}
                                                            className="p-1.5 rounded hover:bg-slate-100 transition-colors shrink-0"
                                                        >
                                                            {copiedField === `pk-m-${key.id}` ? (
                                                                <Check className="w-4 h-4 text-emerald-500" />
                                                            ) : (
                                                                <Copy className="w-4 h-4 text-slate-400" />
                                                            )}
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* Secret Key (masked) */}
                                                <div className="space-y-1">
                                                    <span className="text-xs text-slate-500 uppercase tracking-wide">{__('Secret Key')}</span>
                                                    <code className="block text-xs bg-slate-100 px-2 py-1.5 rounded font-mono text-slate-500" dir="ltr">
                                                        sk_{'••••'}{key.secret_key_last_four}
                                                    </code>
                                                </div>

                                                {/* Meta */}
                                                <div className="flex items-center gap-4 text-xs text-slate-400">
                                                    <span>{__('Last used')}: {formatDate(key.last_used_at)}</span>
                                                    <span>{__('Created')}: {formatDate(key.created_at)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Roll Secret Key Confirmation Dialog */}
            <Dialog open={rollConfirmOpen} onOpenChange={setRollConfirmOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <RefreshCw className="w-5 h-5 text-amber-500" />
                            {__('Roll Secret Key')}
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.this_will_generate_a_new_secret_key_and_immediately_invalidate_the_old_one_any_integrations_using_the_current_secret_key_will_stop_working')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex items-start gap-3 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">
                        <AlertTriangle className="w-5 h-5 mt-0.5 shrink-0" />
                        <p className="text-sm">
                            {__('general.this_action_cannot_be_undone_make_sure_to_update_your_server_configuration_with_the_new_key')}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setRollConfirmOpen(false)}
                            disabled={rolling}
                        >
                            {__('Cancel')}
                        </Button>
                        <Button
                            onClick={handleRollSecret}
                            disabled={rolling}
                            className="bg-amber-600 hover:bg-amber-700 text-white"
                        >
                            {rolling ? (
                                <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                            ) : (
                                <RefreshCw className="w-4 h-4 mr-2" />
                            )}
                            {__('Roll Key')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteConfirmOpen} onOpenChange={setDeleteConfirmOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-600">
                            <Trash2 className="w-5 h-5" />
                            {__('Delete API Key')}
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.are_you_sure_you_want_to_permanently_delete_this_api_key_all_integrations_using_these_keys_will_immediately_stop_working')}
                        </DialogDescription>
                    </DialogHeader>
                    {actionKey && (
                        <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-sm">
                            <p className="font-medium text-red-800">{actionKey.name}</p>
                            <code className="text-xs text-red-600 font-mono mt-1 block" dir="ltr">
                                {actionKey.publishable_key}
                            </code>
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteConfirmOpen(false)}
                            disabled={deleting}
                        >
                            {__('Cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleting}
                        >
                            {deleting ? (
                                <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                            ) : (
                                <Trash2 className="w-4 h-4 mr-2" />
                            )}
                            {__('Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
