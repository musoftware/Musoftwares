import React from 'react';
import { PipelineStage } from '../../Stores/usePipelineStore';
import LeadCard from './LeadCard';
import { Droppable } from '@hello-pangea/dnd';

interface PipelineColumnProps {
    stage: PipelineStage;
}

export default function PipelineColumn({ stage }: PipelineColumnProps) {
    return (
        <div className="flex flex-col flex-shrink-0 w-80 bg-slate-50/50 rounded-xl border border-slate-200 overflow-hidden h-full">
            {/* Column Header */}
            <div className="p-3 border-b border-slate-200 bg-white flex items-center justify-between sticky top-0 z-10">
                <div className="flex items-center gap-2">
                    <span 
                        className="w-3 h-3 rounded-full shadow-inner" 
                        style={{ backgroundColor: stage.color }}
                    />
                    <h3 className="font-semibold text-slate-800 text-sm">{stage.name}</h3>
                </div>
                <div className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs font-bold">
                    {stage.leads.length}
                </div>
            </div>

            {/* Column Body (Scrollable & Droppable) */}
            <Droppable droppableId={`stage-${stage.id}`} type="LEAD">
                {(provided, snapshot) => (
                    <div 
                        ref={provided.innerRef}
                        {...provided.droppableProps}
                        className={`flex-1 overflow-y-auto p-2 scrollbar-thin scrollbar-thumb-slate-200 transition-colors ${
                            snapshot.isDraggingOver ? 'bg-blue-50/50' : ''
                        }`}
                    >
                        {stage.leads.map((lead, index) => (
                            <LeadCard key={lead.id} lead={lead} index={index} />
                        ))}
                        {provided.placeholder}
                    </div>
                )}
            </Droppable>
        </div>
    );
}
