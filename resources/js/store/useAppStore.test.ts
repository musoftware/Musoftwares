import { describe, it, expect, beforeEach } from 'vitest';
import { useAppStore } from './useAppStore';

describe('useAppStore', () => {
    beforeEach(() => {
        // Reset the store state before each test
        const store = useAppStore.getState();
        store.setTheme('system');
        if (store.sidebarOpen) {
            store.toggleSidebar();
        }
    });

    it('should have initial state', () => {
        const state = useAppStore.getState();
        expect(state.theme).toBe('system');
        expect(state.sidebarOpen).toBe(false);
    });

    it('should set theme', () => {
        const store = useAppStore.getState();
        store.setTheme('dark');
        expect(useAppStore.getState().theme).toBe('dark');
    });

    it('should toggle sidebar', () => {
        const store = useAppStore.getState();
        store.toggleSidebar();
        expect(useAppStore.getState().sidebarOpen).toBe(true);
        useAppStore.getState().toggleSidebar();
        expect(useAppStore.getState().sidebarOpen).toBe(false);
    });
});
