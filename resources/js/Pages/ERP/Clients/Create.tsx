import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import {
    LayoutDashboard,
    Users,
    Briefcase,
    CheckSquare,
    FileText,
    History,
    Pin,
    Folder,
    UserCheck,
    Settings,
    CalendarIcon,
    HardDrive,
    UserPlus,
} from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

interface Props {
    currencies: Array<{ id: number; currency: string; name: string }>;
    tenant?: { id: number; name: string; user_id: number };
    hasMultiCurrency?: boolean;
    baseCurrency?: string;
}

export default function CreateClient({ currencies, tenant, hasMultiCurrency = false, baseCurrency = 'USD' }: Props) {
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: '',
        currency: hasMultiCurrency ? 'USD' : baseCurrency,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.clients.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const menuItems = [
        { id: 'overview', label: 'Overview', icon: LayoutDashboard },
        { id: 'clients', label: 'Clients', icon: Users },
        { id: 'projects', label: 'Projects', icon: Briefcase },
        { id: 'tasks', label: 'Tasks', icon: CheckSquare },
        { id: 'invoices', label: 'Invoices', icon: FileText },
        { id: 'transactions', label: 'Transactions', icon: History },
        { id: 'documents', label: 'Files', icon: Folder },
        { id: 'notes', label: 'Notes', icon: Pin },
        { id: 'team', label: 'Team', icon: UserCheck },
        { id: 'backup', label: 'Backup', icon: HardDrive },
        { id: 'settings', label: 'Settings', icon: Settings },
    ].map(m => ({
        ...m,
        isActive: m.id === 'clients',
        href: m.id === 'team'
            ? route('erp.team-members.index')
            : m.id === 'backup'
                ? route('erp.backup.index')
                : route('erp.dashboard', { section: m.id }),
    }));

    const workspaceName = tenant?.name || 'My Workspace';

    return (
        <ERPLayout
            title="Add Client"
            workspaceName={workspaceName}
            tenantId={tenant?.id?.toString() || 'DRAFT'}
            menuItems={menuItems}
        >
            <div className="space-y-6">
                {/* Page Header */}
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">Add New Client</h2>
                    <p className="text-sm text-slate-500 mt-1">Register a new client in your workspace.</p>
                </div>

                {/* Form Card */}
                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <UserPlus className="w-5 h-5" /> Client Details
                        </CardTitle>
                        <CardDescription className="text-slate-500">
                            Provide the necessary information to setup the client profile and wallet.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Client/Company Name <span className="text-red-500">*</span></label>
                                    <Input
                                        required
                                        value={form.name}
                                        onChange={e => setForm({...form, name: e.target.value})}
                                        placeholder="Acme Corp"
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Email Address</label>
                                    <Input
                                        type="email"
                                        value={form.email}
                                        onChange={e => setForm({...form, email: e.target.value})}
                                        placeholder="contact@acme.com"
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.email && <p className="text-xs text-red-500">{errors.email}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Phone Number</label>
                                    <Input
                                        value={form.phone}
                                        onChange={e => setForm({...form, phone: e.target.value})}
                                        placeholder="+1 234 567 890"
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.phone && <p className="text-xs text-red-500">{errors.phone}</p>}
                                </div>
                                {hasMultiCurrency && (
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Billing Currency <span className="text-red-500">*</span></label>
                                    <Select value={form.currency} onValueChange={(val) => setForm({...form, currency: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            {currencies.map(c => (
                                                <SelectItem key={c.currency} value={c.currency}>
                                                    {c.currency} - {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.currency && <p className="text-xs text-red-500">{errors.currency}</p>}
                                </div>
                                )}
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Address</label>
                                    <Input
                                        value={form.address}
                                        onChange={e => setForm({...form, address: e.target.value})}
                                        placeholder="123 Business St, City, Country"
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.address && <p className="text-xs text-red-500">{errors.address}</p>}
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'clients' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Saving...' : 'Save Client'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
