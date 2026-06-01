import React, { useState } from 'react';
import { Head, Link, router, usePage, useForm } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Button } from '@/Components/ui/button';
import { MoreHorizontal, Trash2, Edit2, Mail, Phone, Users, Plus } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import { LeadSlideOver } from '@/Components/CRM/LeadSlideOver';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

export default function Index({ leads, currentTab }) {
    const { auth } = usePage().props;
    const hasSalesStaff = auth?.crm_features?.includes('crm-sales-staff') ?? false;
    const [selectedLeadId, setSelectedLeadId] = useState<number | null>(null);
    const [isSlideOverOpen, setIsSlideOverOpen] = useState(false);
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        company: '',
        message: '',
    });

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        post(route('crm.leads.store'), {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    const handleStatusUpdate = (id, status) => {
        router.post(route('crm.leads.update-status', id), { status });
    };

    const handleDelete = (id) => {
        if (confirm(__('Are you sure you want to delete this lead?'))) {
            router.delete(route('crm.leads.destroy', id));
        }
    };

    const openLead = (id: number) => {
        setSelectedLeadId(id);
        setIsSlideOverOpen(true);
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'converted':
                return <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">{__('Converted')}</span>;
            case 'contacted':
                return <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">{__('Contacted')}</span>;
            case 'dead':
                return <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-800">{__('Dead')}</span>;
            case 'new':
            default:
                return <span className="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">{__('New')}</span>;
        }
    };

    if (!hasSalesStaff) {
        return (
            <CrmLayout title={__('Leads')} activeMenu="leads">
                <ModulePageHeader 
                    title={__('Leads & Pipeline')}
                    description={__('Manage and track your leads pipeline.')}
                    icon={Users}
                    module="CRM"
                />
                <div className="px-8 pb-8">
                    <UpgradeOverlay 
                        title={__('Sales Staff Add-on Required')}
                        description={__('To manage leads, track pipelines, and assign salespeople, you need the Sales Staff Operations add-on.')}
                        icon={Users}
                        module="crm-sales-staff"
                        priceText={__('Subscribe to Sales Staff')}
                    />
                </div>
            </CrmLayout>
        );
    }

    return (
        <CrmLayout title={__('Leads')} activeMenu="leads">
            <ModulePageHeader 
                title={__('Leads CRM')}
                description={__('Manage and track your leads pipeline.')}
                icon={Users}
                module="CRM"
                actions={
                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button className="bg-indigo-600 hover:bg-indigo-700 text-white font-medium flex items-center gap-1.5 shadow-sm rounded-lg py-2 px-4 transition duration-150">
                                <Plus className="h-4 w-4" />
                                {__('crm.create_lead')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md max-w-full">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-slate-900 mb-1">{__('crm.add_new_lead')}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreateSubmit} className="space-y-4 py-2">
                                <div className="space-y-1">
                                    <Label htmlFor="name" className="text-slate-700 font-medium">{__('crm.lead_name')} <span className="text-red-500">*</span></Label>
                                    <Input 
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={e => setData('name', e.target.value)}
                                        placeholder={__('crm.lead_name')}
                                        required
                                        className="w-full"
                                    />
                                    {errors.name && <div className="text-xs text-red-500 mt-1">{errors.name}</div>}
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1">
                                        <Label htmlFor="phone" className="text-slate-700 font-medium">{__('crm.lead_phone')}</Label>
                                        <Input 
                                            id="phone"
                                            type="tel"
                                            value={data.phone}
                                            onChange={e => setData('phone', e.target.value)}
                                            placeholder={__('crm.lead_phone')}
                                            className="w-full"
                                        />
                                        {errors.phone && <div className="text-xs text-red-500 mt-1">{errors.phone}</div>}
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="email" className="text-slate-700 font-medium">{__('crm.lead_email')}</Label>
                                        <Input 
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={e => setData('email', e.target.value)}
                                            placeholder={__('crm.lead_email')}
                                            className="w-full"
                                        />
                                        {errors.email && <div className="text-xs text-red-500 mt-1">{errors.email}</div>}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="company" className="text-slate-700 font-medium">{__('crm.lead_company')}</Label>
                                    <Input 
                                        id="company"
                                        type="text"
                                        value={data.company}
                                        onChange={e => setData('company', e.target.value)}
                                        placeholder={__('crm.lead_company')}
                                        className="w-full"
                                    />
                                    {errors.company && <div className="text-xs text-red-500 mt-1">{errors.company}</div>}
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="message" className="text-slate-700 font-medium">{__('crm.lead_message')}</Label>
                                    <Textarea 
                                        id="message"
                                        value={data.message}
                                        onChange={e => setData('message', e.target.value)}
                                        placeholder={__('crm.lead_message')}
                                        className="w-full min-h-20"
                                    />
                                    {errors.message && <div className="text-xs text-red-500 mt-1">{errors.message}</div>}
                                </div>
                                <DialogFooter className="mt-6 flex flex-row items-center justify-end gap-2 border-t pt-4">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        onClick={() => setIsCreateOpen(false)}
                                        className="border-slate-200 hover:bg-slate-50 transition-colors"
                                    >
                                        {__('crm.cancel')}
                                    </Button>
                                    <Button 
                                        type="submit" 
                                        disabled={processing}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                                    >
                                        {processing ? __('crm.submitting') : __('crm.create_lead')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                }
            />
            <div className="flex-1 space-y-4 px-8 pb-8">

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex space-x-2 bg-slate-100/50 p-1 rounded-lg border border-slate-200">
                        <Link
                            href={route('crm.leads.index', { status: 'all' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'all' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            {__('All Leads')}
                        </Link>
                        <Link
                            href={route('crm.leads.index', { status: 'new' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'new' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            {__('New')}
                        </Link>
                        <Link
                            href={route('crm.leads.index', { status: 'contacted' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'contacted' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            {__('Contacted')}
                        </Link>
                        <Link
                            href={route('crm.leads.index', { status: 'converted' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'converted' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            {__('Converted')}
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/50">
                            <tr>
                                <th className="px-6 py-4 font-semibold text-slate-600">{__('Name')}</th>
                                <th className="px-6 py-4 font-semibold text-slate-600">{__('Contact')}</th>
                                <th className="px-6 py-4 font-semibold text-slate-600">{__('Company')}</th>
                                <th className="px-6 py-4 font-semibold text-slate-600">{__('Message Snippet')}</th>
                                <th className="px-6 py-4 font-semibold text-slate-600 text-center">{__('Status')}</th>
                                <th className="px-6 py-4 font-semibold text-slate-600 text-right">{__('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {leads.data.map((lead) => (
                                <tr key={lead.id} className="hover:bg-slate-50/80 cursor-pointer transition-colors" onClick={() => openLead(lead.id)}>
                                    <td className="px-6 py-4 font-medium text-slate-900">{lead.name || __('Unknown')}</td>
                                    <td className="px-6 py-4">
                                        <div className="flex flex-col space-y-1.5">
                                            {lead.email && (
                                                <a href={`mailto:${lead.email}`} onClick={(e) => e.stopPropagation()} className="text-indigo-600 hover:text-indigo-700 flex items-center text-xs font-medium">
                                                    <Mail className="h-3.5 w-3.5 mr-1.5 opacity-70" /> {lead.email}
                                                </a>
                                            )}
                                            {lead.phone && (
                                                <a href={`tel:${lead.phone}`} onClick={(e) => e.stopPropagation()} className="text-slate-600 hover:text-slate-900 flex items-center text-xs font-medium">
                                                    <Phone className="h-3.5 w-3.5 mr-1.5 opacity-70" /> {lead.phone}
                                                </a>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-slate-700">{lead.company || '-'}</td>
                                    <td className="px-6 py-4 text-slate-500 max-w-xs truncate" title={lead.message}>
                                        {lead.message || '-'}
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        {getStatusBadge(lead.status)}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger render={<Button variant="ghost" className="h-8 w-8 p-0 text-slate-400 hover:text-slate-900" />} onClick={(e) => e.stopPropagation()}>
                                                <span className="sr-only">{__('Open menu')}</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48">
                                                <DropdownMenuLabel>{__('Actions')}</DropdownMenuLabel>
                                                <DropdownMenuItem onClick={(e) => { e.stopPropagation(); openLead(lead.id); }}>
                                                    <Edit2 className="mr-2 h-4 w-4 text-slate-500" />
                                                    {__('View / Edit')}
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {['new', 'contacted', 'converted', 'dead'].map((status) => (
                                                    lead.status !== status && (
                                                        <DropdownMenuItem key={status} onClick={(e) => { e.stopPropagation(); handleStatusUpdate(lead.id, status); }}>
                                                            {__('Mark as')} {__(status.charAt(0).toUpperCase() + status.slice(1))}
                                                        </DropdownMenuItem>
                                                    )
                                                ))}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={(e) => { e.stopPropagation(); handleDelete(lead.id); }} className="text-red-600 focus:text-red-600">
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    {__('Delete Lead')}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                            {leads.data.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-slate-500">
                                        {__('No leads found for this status.')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {leads.links && leads.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <div className="inline-flex -space-x-px rounded-lg shadow-sm">
                            {leads.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'} ${i === 0 ? 'rounded-l-lg' : ''} ${i === leads.links.length - 1 ? 'rounded-r-lg' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
                
                <LeadSlideOver 
                    leadId={selectedLeadId} 
                    isOpen={isSlideOverOpen} 
                    onClose={() => setIsSlideOverOpen(false)} 
                />
            </div>
        </CrmLayout>
    );
}
