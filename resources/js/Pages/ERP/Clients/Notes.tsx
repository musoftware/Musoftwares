import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ClientPageLayout from './Components/ClientPageLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { __ } from '@/lib/i18n';
import { StickyNote } from 'lucide-react';

interface Props {
    client: any;
    balance: number;
    lockedBalance: number;
    totalRevenue: number;
    unpaidRevenue: number;
    projectsCount: number;
    ticketsCount: number;
    hasTickets: boolean;
    notes: any[];
}

export default function ClientNotes({
    client, balance, lockedBalance, totalRevenue, unpaidRevenue, projectsCount, ticketsCount, hasTickets, notes
}: Props) {

    return (
        <ClientPageLayout
            client={client}
            balance={balance}
            lockedBalance={lockedBalance}
            totalRevenue={totalRevenue}
            unpaidRevenue={unpaidRevenue}
            projectsCount={projectsCount}
            ticketsCount={ticketsCount}
            hasTickets={hasTickets}
            activeTab="notes"
        >
            <Card className="bg-white border border-slate-200 shadow-sm">
                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="text-slate-900 text-lg font-semibold">{__('general.secure_notes')}</CardTitle>
                        <p className="text-sm text-slate-500 mt-1">{__('general.internal_notes_about_this_client')}</p>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {notes.length === 0 ? (
                        <EmptyState icon={StickyNote} title={__('general.no_notes')} description={__('general.no_internal_notes_have_been_added')} />
                    ) : (
                        <div className="divide-y divide-slate-100 border-t border-slate-100">
                            {notes.map(note => (
                                <div key={note.id} className="p-6 hover:bg-slate-50 transition-colors">
                                    <div className="flex items-center justify-between mb-2">
                                        <Badge variant="outline" className="text-[10px] uppercase tracking-wider font-semibold">
                                            {note.type}
                                        </Badge>
                                        <p className="text-xs text-slate-400"><DateDisplay date={note.created_at} format="datetime" /></p>
                                    </div>
                                    <p className="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{note.content}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </ClientPageLayout>
    );
}
