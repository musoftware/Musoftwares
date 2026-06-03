import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { Users, Mail, PlayCircle, BarChart3, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';

export default function Dashboard({ stats }) {
    const { auth } = usePage().props;

    const cards = [
        {
            title: __('general.leads'),
            description: __('general.manage_and_track_your_leads_pipeline'),
            icon: Users,
            href: route('crm.leads.index'),
            stats: `${stats.total_leads} ${__('Total')} (${stats.new_leads} ${__('general.new_1')})`,
            color: 'text-blue-600',
            bg: 'bg-blue-100',
        },
        {
            title: __('general.campaigns'),
            description: __('general.email_marketing_campaigns'),
            icon: Mail,
            href: route('crm.campaigns.index'),
            stats: `${stats.active_campaigns} ${__('general.active_campaigns')}`,
            color: 'text-indigo-600',
            bg: 'bg-indigo-100',
        },
        {
            title: __('general.sequences'),
            description: __('general.automated_follow_up_sequences'),
            icon: PlayCircle,
            href: route('crm.sequences.index'),
            stats: `${stats.total_sequences} ${__('general.sequences')}`,
            color: 'text-purple-600',
            bg: 'bg-purple-100',
        },
    ];

    return (
        <CrmLayout title={__('general.welcome')} activeMenu="dashboard">
            <ModulePageHeader 
                title={__('general.crm_dashboard')}
                description={__('general.overview_of_your_marketing_leads_and_automations')}
                icon={BarChart3}
                
            />

            <div className="px-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {cards.map((card) => (
                    <Card key={card.title} className="hover:shadow-lg transition-shadow duration-200 border-slate-200/60 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xl font-bold tracking-tight">
                                {card.title}
                            </CardTitle>
                            <div className={`p-2 rounded-lg ${card.bg}`}>
                                <card.icon className={`h-6 w-6 ${card.color}`} />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription className="mb-4 text-slate-500">
                                {card.description}
                            </CardDescription>
                            <div className="text-sm font-medium text-slate-700 mb-6 bg-slate-50 p-2 rounded-md inline-block">
                                {card.stats}
                            </div>
                            <div className="flex items-center mt-2">
                                <Link 
                                    href={card.href} 
                                    className="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
                                >
                                    {__('general.go_to')} {card.title} <ArrowRight className="ml-1 h-4 w-4" />
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </CrmLayout>
    );
}
