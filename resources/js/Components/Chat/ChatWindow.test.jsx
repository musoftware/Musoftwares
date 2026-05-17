import { render, screen, waitFor } from '@testing-library/react';
import ChatWindow from './ChatWindow';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock inertia usePage
vi.mock('@inertiajs/react', () => ({
    usePage: vi.fn(),
}));

// Mock axios
vi.mock('axios');

// Mock Message component
vi.mock('./Message', () => ({
    default: () => <div data-testid="message" />
}));

describe('ChatWindow', () => {
    let consoleErrorSpy;

    beforeEach(() => {
        usePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Test User' } }
            }
        });

        // Mock scrollIntoView
        window.HTMLElement.prototype.scrollIntoView = vi.fn();

        // Mock console.error using vi.spyOn to allow restoration
        consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('logs an error and shows error UI when fetching initial messages fails', async () => {
        const errorMessage = 'Network Error';
        axios.get.mockRejectedValueOnce(new Error(errorMessage));

        render(<ChatWindow conversationId={123} participants={[]} />);

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith('/api/conversations/123/messages');
        });

        // Verify console.error was called
        expect(consoleErrorSpy).toHaveBeenCalledWith('Error fetching messages:', expect.any(Error));

        // Verify UI shows the error state by awaiting the element
        const errorElement = await screen.findByTestId('error-message');
        expect(errorElement).toBeInTheDocument();
        expect(errorElement).toHaveTextContent('Failed to load messages. Please try again.');
    });
});
