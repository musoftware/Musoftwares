import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Megaphone, Target, ArrowRight } from 'lucide-react';

export default function Dashboard({ auth, stats, recentLeads }) {
    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">CRM Dashboard</h2>}>
            <Head title="CRM Dashboard" />

            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                {/* Stats Row */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Leads</CardTitle>
                            <Users className="h-4 w-4 text-slate-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_leads}</div>
                            <p className="text-xs text-slate-500">Captured across all campaigns</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">New Leads</CardTitle>
                            <Target className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600">{stats.new_leads}</div>
                            <p className="text-xs text-slate-500">Awaiting your review</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Campaigns</CardTitle>
                            <Megaphone className="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-indigo-600">{stats.active_campaigns}</div>
                            <p className="text-xs text-slate-500">Currently generating leads</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Leads */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Leads</CardTitle>
                            <p className="text-sm text-slate-500 mt-1">The latest contacts captured from your campaigns.</p>
                        </div>
                        <Link href={route('crm.leads.index')} className="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center">
                            View All <ArrowRight className="ml-1 h-4 w-4" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        {recentLeads.length === 0 ? (
                            <div className="text-center py-8 text-slate-500">
                                No leads captured yet. Create a campaign and embed the form to start generating leads!
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {recentLeads.map(lead => (
                                    <div key={lead.id} className="flex items-center justify-between border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                                        <div>
                                            <p className="font-medium text-slate-900">{lead.name}</p>
                                            <p className="text-sm text-slate-500">{lead.email} &bull; {lead.campaign?.name}</p>
                                        </div>
                                        <div className="text-xs font-semibold px-2 py-1 rounded-full bg-blue-50 text-blue-700 capitalize">
                                            {lead.status}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

            </div>
        </AuthenticatedLayout>
    );
}
