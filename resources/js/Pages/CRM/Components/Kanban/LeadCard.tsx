import React from 'react';
import { router } from '@inertiajs/react';
import { PipelineLead } from '../../Stores/usePipelineStore';

import { Phone, MessageCircle, Clock, AlertTriangle } from 'lucide-react';
import { Draggable } from '@hello-pangea/dnd';
import { __ } from '@/lib/i18n';

interface LeadCardProps {
    lead: PipelineLead;
    index: number;
}

export default function LeadCard({ lead, index }: LeadCardProps) {
    const openDrawer = (id: number) => { 
        router.visit((window as any).route('crm.leads.show', id));
    };

    // Dynamic SLA coloring
    const isSLABreached = lead.slaBreached;
    const slaColor = isSLABreached ? 'text-red-600 bg-red-50 border-red-200' : 'text-slate-500 bg-slate-50 border-slate-100';

    return (
        <Draggable draggableId={`lead-${lead.id}`} index={index}>
            {(provided, snapshot) => (
                <div 
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    {...provided.dragHandleProps}
                    onClick={() => openDrawer(lead.id)}
                    className={`group relative bg-white border rounded-lg p-3 shadow-sm hover:border-blue-300 transition-all mb-3 select-none ${
                        snapshot.isDragging ? 'border-blue-500 shadow-xl rotate-2 ring-2 ring-blue-200 z-50' : 'border-slate-200 hover:shadow-md'
                    }`}
                >
                    <div className="flex justify-between items-start mb-2">
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {lead.name.charAt(0)}
                            </div>
                            <div className="min-w-0">
                                <h4 className="font-semibold text-sm text-slate-800 leading-tight truncate">{lead.name}</h4>
                                <span className="text-xs text-slate-500 block truncate">{lead.source}</span>
                            </div>
                        </div>
                        {lead.score > 0 && (
                            <div className="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold shrink-0">
                                {lead.score}
                            </div>
                        )}
                    </div>

                    <div className={`flex items-center gap-1.5 px-2 py-1 rounded-md border text-xs w-max mt-2 ${slaColor}`}>
                        {isSLABreached ? <AlertTriangle size={12} /> : <Clock size={12} />}
                        <span className="font-medium">
                            {isSLABreached ? 'SLA Breached' : '2h left'}
                        </span>
                    </div>

                    </div>
            )}
        </Draggable>
    );
}
