import React from 'react';
import { __ } from '@/lib/i18n';
import { AlertTriangle, Clock, UserX, ArrowRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Link } from '@inertiajs/react';

interface StaleLead {
    id: number;
    name: string;
    phone: string;
    pipeline_stage: string;
    updated_at: string;
    assigned_to_id: number | null;
}

export default function SlaAlertsList({ leads = [] }: { leads: StaleLead[] }) {
    if (!leads || leads.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center p-6 text-sm text-slate-500 border border-dashed rounded-lg bg-emerald-50/30">
                <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                </div>
                <span className="font-medium text-emerald-800">{__('Zero SLA Breaches')}</span>
                <span className="text-emerald-600/80 mt-1">{__('All leads are being followed up on time.')}</span>
            </div>
        );
    }

    const getTimeAgo = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
        
        if (diffHours < 24) return `${diffHours} ${__('hours ago')}`;
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays} ${__('days ago')}`;
    };

    return (
        <div className="space-y-3">
            {leads.map((lead) => (
                <div key={lead.id} className="flex items-center justify-between p-3 border rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5">
                            {lead.assigned_to_id ? (
                                <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center" title={__('Stale Lead')}>
                                    <Clock className="w-4 h-4 text-red-600" />
                                </div>
                            ) : (
                                <div className="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center" title={__('Unassigned Lead')}>
                                    <UserX className="w-4 h-4 text-amber-600" />
                                </div>
                            )}
                        </div>
                        <div className="flex flex-col">
                            <span className="font-medium text-slate-900">{lead.name}</span>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant="secondary" className="text-[10px] px-1.5 h-4 font-normal">
                                    {__(lead.pipeline_stage)}
                                </Badge>
                                <span className="text-xs text-slate-500 flex items-center gap-1">
                                    <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                                    {__('Last touched:')} <span className="font-medium text-red-600">{getTimeAgo(lead.updated_at)}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <Button variant="ghost" size="sm" asChild className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
                        <Link href={`/crm/leads/${lead.id}`}>
                            {__('View')}
                            <ArrowRight className="w-4 h-4 ml-1" />
                        </Link>
                    </Button>
                </div>
            ))}
        </div>
    );
}

// Need CheckCircle2 for empty state
import { CheckCircle2 } from 'lucide-react';
