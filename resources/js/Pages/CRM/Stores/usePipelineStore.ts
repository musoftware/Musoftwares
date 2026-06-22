import { create } from 'zustand';
import axios from 'axios';
import { __ } from '@/lib/i18n';

export interface PipelineLead {
    id: number;
    name: string;
    source: string;
    score: number;
    stageId: number;
    slaBreached: boolean;
}

export interface PipelineStage {
    id: number;
    name: string;
    color: string;
    leads: PipelineLead[];
}

interface PipelineState {
    stages: PipelineStage[];
    isLoading: boolean;
    setStages: (stages: PipelineStage[]) => void;
    fetchPipeline: () => Promise<void>;
    moveLead: (leadId: number, sourceStageId: number, destStageId: number, destinationIndex: number) => void;
}

export const usePipelineStore = create<PipelineState>((set, get) => ({
    stages: [],
    isLoading: false,
    
    setStages: (stages) => set({ stages }),

    fetchPipeline: async () => {
        set({ isLoading: true });
        try {
            const response = await axios.get('/crm/api/kanban');
            set({ stages: response.data, isLoading: false });
        } catch (error: any) {
            console.error("Failed to fetch pipeline", error);
            set({ isLoading: false });
        }
    },

    moveLead: (leadId, sourceStageId, destStageId, destinationIndex) => {
        set((state) => {
            const newStages = [...state.stages];
            
            const sourceStageIndex = newStages.findIndex(s => s.id === sourceStageId);
            const destStageIndex = newStages.findIndex(s => s.id === destStageId);
            
            if (sourceStageIndex === -1 || destStageIndex === -1) return state;

            const sourceStage = newStages[sourceStageIndex];
            const destStage = newStages[destStageIndex];

            const leadIndex = sourceStage.leads.findIndex(l => l.id === leadId);
            if (leadIndex === -1) return state;

            // Remove from source
            const [movedLead] = sourceStage.leads.splice(leadIndex, 1);
            
            // Update stageId reference
            movedLead.stageId = destStageId;

            // Add to destination
            destStage.leads.splice(destinationIndex, 0, movedLead);

            // Trigger background API call (fire and forget)
            axios.put(`/crm/api/kanban/${leadId}/stage`, { 
                stage_id: destStageId,
                position: destinationIndex
            }).catch(e => console.error("Failed to update lead stage", e));

            return { stages: newStages };
        });
    }
}));

