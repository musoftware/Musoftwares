import React from 'react';
import { Activity, Phone, ArrowRight, MessageSquare } from 'lucide-react';

interface FeedItem {
    id: number;
    type: 'call' | 'stage_change' | 'note' | 'assignment';
    agentName: string;
    leadName: string;
    description: string;
    timeAgo: string;
}

export default function ActivityFeed({ feed }: { feed?: FeedItem[] }) {
    const displayFeed = feed || [];

    const getIcon = (type: string) => {
        switch (type) {
            case 'stage_change': return <ArrowRight size={14} className="text-blue-600" />;
            case 'call': return <Phone size={14} className="text-green-600" />;
            case 'note': return <MessageSquare size={14} className="text-purple-600" />;
            default: return <Activity size={14} className="text-slate-600" />;
        }
    };

    return (
        <div className="bg-white rounded-xl border border-slate-200 flex flex-col h-full shadow-sm">
            <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 className="font-semibold text-slate-800">{__('general.live_activity_feed')}</h3>
                <span className="flex h-2 w-2 relative">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
            </div>
            
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
                {displayFeed.length === 0 ? (
                    <div className="text-sm text-slate-500 text-center py-4">{__('general.no_recent_activities')}</div>
                ) : (
                    displayFeed.map((item) => (
                    <div key={item.id} className="flex gap-3 text-sm">
                        <div className="mt-1 w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                            {getIcon(item.type)}
                        </div>
                        <div>
                            <p className="text-slate-800">
                                <span className="font-semibold">{item.agentName}</span> {item.description} <span className="font-medium text-blue-600">{item.leadName}</span>
                            </p>
                            <span className="text-xs text-slate-400">{item.timeAgo}</span>
                        </div>
                    </div>
                    ))
                )}
            </div>
        </div>
    );
}
