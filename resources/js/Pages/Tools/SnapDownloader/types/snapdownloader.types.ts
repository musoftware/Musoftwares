export interface Process {
    id: string;
    target: string;
    status: 'running' | 'completed' | 'error' | 'stopped';
    progress: number;
    progressMsg: string;
    startTime: string;
    successCount: number;
    totalItems: number;
    logs: { ts: number; level: string; message: string }[];
    outputDir: string | null;
    endTime?: string;
}

export interface QueueJob {
    id: string;
    target: string;
    status: 'pending' | 'running';
    addedAt: string;
    filters: Record<string, boolean>;
}

export interface SavedFolder {
    name: string;
    fileCount: number;
    totalSize: number;
    path: string;
}

export type WorkspaceType = 'new' | 'active' | 'queue' | 'automations' | 'folders' | 'history';
