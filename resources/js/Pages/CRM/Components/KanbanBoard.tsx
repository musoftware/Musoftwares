import React from 'react';

export function KanbanBoard({ pipeline }: { pipeline: any }) {
    return (
        <div className="flex gap-4 overflow-x-auto pb-4">
            {pipeline.stages?.map((stage: any) => (
                <div key={stage.id} className="min-w-[300px] bg-gray-50 rounded-lg p-4 shadow-sm border border-gray-200">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="font-semibold text-gray-700 flex items-center gap-2">
                            <span 
                                className="w-3 h-3 rounded-full" 
                                style={{ backgroundColor: stage.color || '#cbd5e1' }}
                            ></span>
                            {stage.name}
                        </h3>
                        <span className="text-xs font-medium bg-white px-2 py-1 rounded-full border">
                            {stage.leads?.length || 0}
                        </span>
                    </div>

                    <div className="space-y-3">
                        {stage.leads?.map((lead: any) => (
                            <div key={lead.id} className="bg-white p-3 rounded shadow-sm border border-gray-100 cursor-pointer hover:border-blue-300">
                                <div className="font-medium">{lead.name}</div>
                                {lead.company && <div className="text-sm text-gray-500">{lead.company}</div>}
                                <div className="mt-2 flex items-center justify-between text-xs text-gray-400">
                                    <span>{lead.created_at?.split('T')[0]}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
