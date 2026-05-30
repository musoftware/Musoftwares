import { create } from 'zustand';

interface CRMState {
    isSidebarCollapsed: boolean;
    activeWorkspaceTab: 'pipeline' | 'tasks' | 'reports';
    setSidebarCollapsed: (collapsed: boolean) => void;
    toggleSidebar: () => void;
    setActiveWorkspaceTab: (tab: 'pipeline' | 'tasks' | 'reports') => void;
}

export const useCRMStore = create<CRMState>((set) => ({
    isSidebarCollapsed: false,
    activeWorkspaceTab: 'pipeline',
    setSidebarCollapsed: (collapsed) => set({ isSidebarCollapsed: collapsed }),
    toggleSidebar: () => set((state) => ({ isSidebarCollapsed: !state.isSidebarCollapsed })),
    setActiveWorkspaceTab: (tab) => set({ activeWorkspaceTab: tab }),
}));
