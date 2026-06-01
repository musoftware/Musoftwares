import React, { useEffect } from 'react';
import { usePipelineStore } from '../../Stores/usePipelineStore';
import PipelineColumn from './PipelineColumn';
import { DragDropContext, DropResult } from '@hello-pangea/dnd';
import { __ } from '@/lib/i18n';

export default function PipelineBoard() {
    const { stages, isLoading, fetchPipeline } = usePipelineStore();

    useEffect(() => {
        fetchPipeline();
    }, [fetchPipeline]);

    const onDragEnd = (result: DropResult) => {
        const { source, destination, draggableId } = result;

        // Dropped outside a valid droppable
        if (!destination) return;

        // Dropped in the same exact position
        if (source.droppableId === destination.droppableId && source.index === destination.index) {
            return;
        }

        const leadId = parseInt(draggableId.replace('lead-', ''), 10);
        const sourceStageId = parseInt(source.droppableId.replace('stage-', ''), 10);
        const destStageId = parseInt(destination.droppableId.replace('stage-', ''), 10);

        usePipelineStore.getState().moveLead(leadId, sourceStageId, destStageId, destination.index);

        // Here we would also trigger an API call to save the new stage to the backend
        // e.g. axios.put(`/api/crm/leads/${leadId}/stage`, { stage_id: destStageId, position: destination.index })
    };

    if (isLoading) {
        return <div className="flex items-center justify-center h-full text-slate-500">{__('general.loading_pipeline')}</div>;
    }

    return (
        <DragDropContext onDragEnd={onDragEnd}>
            <div className="flex gap-4 h-full overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-slate-200">
                {stages.map((stage) => (
                    <PipelineColumn key={stage.id} stage={stage} />
                ))}
            </div>
        </DragDropContext>
    );
}
