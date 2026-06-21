import { create } from 'zustand';
import { subscribeWithSelector, persist } from 'zustand/middleware';

export interface AppState {
    theme: 'light' | 'dark' | 'system';
    sidebarOpen: boolean;
}

export interface AppActions {
    setTheme: (theme: 'light' | 'dark' | 'system') => void;
    toggleSidebar: () => void;
}

export type AppStore = AppState & AppActions;

export const useAppStore = create<AppStore>()(
    subscribeWithSelector(
        persist(
            (set) => ({
                theme: 'system',
                sidebarOpen: false,
                setTheme: (theme) => set({ theme }),
                toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
            }),
            {
                name: 'app-storage',
            }
        )
    )
);
