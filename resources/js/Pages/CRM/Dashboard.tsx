import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
import { Users, Mail, PlayCircle, BarChart3, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';

export default function Dashboard({ stats }) {
    const { auth } = usePage().props;

    const cards = [
        {
            title: 'Leads',
            description: 'Manage and track your leads pipeline',
            icon: Users,
            href: route('crm.leads.index'),
            stats: `${stats.total_leads} Total (${stats.new_leads} New)`,
            color: 'text-blue-600',
            bg: 'bg-blue-100',
        },
        {
            title: 'Campaigns',
            description: 'Email and WhatsApp marketing campaigns',
            icon: Mail,
            href: route('crm.campaigns.index'),
            stats: `${stats.active_campaigns} Active Campaigns`,
            color: 'text-indigo-600',
            bg: 'bg-indigo-100',
        },
        {
            title: 'Sequences',
            description: 'Automated follow-up sequences',
            icon: PlayCircle,
            href: route('crm.sequences.index'),
            stats: `${stats.total_sequences} Sequences`,
            color: 'text-purple-600',
            bg: 'bg-purple-100',
        },
    ];

    return (
        <CrmLayout title="Dashboard" activeMenu="dashboard">
            <div className="mb-8">
                <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight flex items-center">
                    <BarChart3 className="mr-3 h-8 w-8 text-indigo-600" />
                    CRM Dashboard
                </h2>
                <p className="mt-2 text-sm text-gray-600">
                    Overview of your marketing, leads, and automations.
                </p>
            </div>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {cards.map((card) => (
                    <Card key={card.title} className="hover:shadow-lg transition-shadow duration-200">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xl font-bold">
                                {card.title}
                            </CardTitle>
                            <div className={`p-2 rounded-lg ${card.bg}`}>
                                <card.icon className={`h-6 w-6 ${card.color}`} />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription className="mb-4">
                                {card.description}
                            </CardDescription>
                            <div className="text-sm font-medium text-gray-700 mb-6">
                                {card.stats}
                            </div>
                            <Link 
                                href={card.href} 
                                className="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-500"
                            >
                                Go to {card.title} <ArrowRight className="ml-1 h-4 w-4" />
                            </Link>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </CrmLayout>
    );
}
