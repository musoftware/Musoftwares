import React from 'react';
import { __ } from '@/lib/i18n';
import { Megaphone, ArrowRight, CheckCircle2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Link } from '@inertiajs/react';

interface Campaign {
    id: number;
    name: string;
    status: string;
    progress: number;
    sent_count: number;
    total_recipients: number;
    scheduled_at: string | null;
}

export default function ManagerCampaignsList({ campaigns = [] }: { campaigns: Campaign[] }) {
    if (!campaigns || campaigns.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center p-6 text-sm text-slate-500 border border-dashed rounded-lg bg-slate-50/30">
                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <CheckCircle2 className="w-5 h-5 text-slate-400" />
                </div>
                <span className="font-medium text-slate-700">{__('general.no_active_campaigns')}</span>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {campaigns.map((campaign) => (
                <div key={campaign.id} className="flex flex-col gap-2 p-3 border rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <Megaphone className="w-4 h-4 text-purple-600" />
                            </div>
                            <div className="flex flex-col">
                                <span className="font-medium text-slate-900">{campaign.name}</span>
                                <div className="flex items-center gap-2 mt-1">
                                    <Badge variant="secondary" className="text-[10px] px-1.5 h-4 font-normal">
                                        {campaign.status}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <Button variant="ghost" size="sm" asChild className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
                            <Link href={`/crm/campaigns/${campaign.id}`}>
                                {__('general.view')}
                                <ArrowRight className="w-4 h-4 ml-1" />
                            </Link>
                        </Button>
                    </div>
                    
                    <div className="flex flex-col gap-1 mt-1 pl-11">
                        <div className="flex justify-between text-xs text-slate-500">
                            <span>{__('general.progress')}</span>
                            <span>{campaign.sent_count} / {campaign.total_recipients}</span>
                        </div>
                        <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div className="bg-purple-600 h-full transition-all" style={{ width: `${campaign.progress}%` }} />
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
