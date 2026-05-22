import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function LeadsIndex({ auth, leads }) {
    const { patch } = useForm({});

    const handleStatusChange = (leadId, newStatus) => {
        patch(route('crm.leads.updateStatus', leadId), {
            data: { status: newStatus },
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">All Leads</h2>}>
            <Head title="Leads" />

            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <Card>
                    <CardHeader>
                        <CardTitle>Lead Database</CardTitle>
                        <CardDescription>Manage and track all contacts captured across your campaigns.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {(!leads.data || leads.data.length === 0) ? (
                            <div className="text-center py-12 text-slate-500 border border-dashed rounded-lg border-slate-200">
                                No leads captured yet.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left">
                                    <thead className="text-xs text-slate-500 uppercase bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 rounded-tl-lg">Name</th>
                                            <th className="px-4 py-3">Contact</th>
                                            <th className="px-4 py-3">Campaign</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3 rounded-tr-lg">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {leads.data.map(lead => (
                                            <tr key={lead.id} className="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                                                <td className="px-4 py-3 font-medium text-slate-900">{lead.name}</td>
                                                <td className="px-4 py-3">
                                                    <div className="text-slate-900">{lead.email}</div>
                                                    <div className="text-slate-500 text-xs">{lead.phone || 'No phone'}</div>
                                                </td>
                                                <td className="px-4 py-3 text-slate-600">
                                                    {lead.campaign ? lead.campaign.name : 'Unknown Source'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Select
                                                        defaultValue={lead.status || 'new'}
                                                        onValueChange={(val) => handleStatusChange(lead.id, val)}
                                                    >
                                                        <SelectTrigger className="w-[130px] h-8 text-xs bg-white">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="new">New</SelectItem>
                                                            <SelectItem value="contacted">Contacted</SelectItem>
                                                            <SelectItem value="qualified">Qualified</SelectItem>
                                                            <SelectItem value="lost">Lost</SelectItem>
                                                            <SelectItem value="converted">Converted</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td className="px-4 py-3 text-slate-500">{new Date(lead.created_at).toLocaleDateString()}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        
                        {/* Basic Pagination (if needed) */}
                        <div className="mt-4 flex justify-center">
                            {/* Render pagination links here */}
                        </div>
                    </CardContent>
                </Card>

            </div>
        </AuthenticatedLayout>
    );
}
