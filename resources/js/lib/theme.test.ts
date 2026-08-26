import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { applyTheme, initTheme, isDarkActive } from './theme';
import { useAppStore } from '@/store/useAppStore';

describe('Theme auto system mode utility', () => {
    let matchMediaListeners: Array<(e: any) => void> = [];
    let matchesSystemDark = false;

    beforeEach(() => {
        document.documentElement.className = '';
        matchMediaListeners = [];
        matchesSystemDark = false;

        window.matchMedia = vi.fn().mockImplementation((query: string) => ({
            matches: matchesSystemDark,
            media: query,
            onchange: null,
            addListener: vi.fn((cb) => matchMediaListeners.push(cb)),
            removeListener: vi.fn(),
            addEventListener: vi.fn((_, cb) => matchMediaListeners.push(cb)),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }));

        useAppStore.getState().setTheme('system');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('should correctly evaluate system auto mode when system is light', () => {
        matchesSystemDark = false;
        expect(isDarkActive('system')).toBe(false);
        applyTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('should correctly evaluate system auto mode when system is dark', () => {
        matchesSystemDark = true;
        expect(isDarkActive('system')).toBe(true);
        applyTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('should explicitly force dark mode regardless of system', () => {
        matchesSystemDark = false;
        applyTheme('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('should explicitly force light mode regardless of system', () => {
        matchesSystemDark = true;
        applyTheme('light');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('should listen to real-time OS preference changes when in system mode', () => {
        matchesSystemDark = false;
        const cleanup = initTheme();
        expect(document.documentElement.classList.contains('dark')).toBe(false);

        // System switches to dark
        matchesSystemDark = true;
        window.matchMedia = vi.fn().mockImplementation((query: string) => ({
            matches: true,
            media: query,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        }));

        // Fire media query change event
        matchMediaListeners.forEach((listener) => listener({ matches: true }));

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        cleanup();
    });
});
