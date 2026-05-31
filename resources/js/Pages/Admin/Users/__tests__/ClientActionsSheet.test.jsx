import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ClientActionsSheet from '../ClientActionsSheet';
import { Link } from '@inertiajs/react';

// Mock Inertia Link
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, className }) => <a href={href} className={className}>{children}</a>,
}));

// Mock ResizeObserver which might be needed for Radix UI components
class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
}
window.ResizeObserver = ResizeObserver;

describe('ClientActionsSheet', () => {
    const mockClient = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
    };

    it('renders all action buttons and correct links', () => {
        render(
            <ClientActionsSheet
                client={mockClient}
                isOpen={true}
                onClose={vi.fn()}
                onLoginAs={vi.fn()}
                onResetPassword={vi.fn()}
            />
        );

        // Check for client info
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();

        // Helper to check if a link with specific text has the correct href
        const expectLink = (text, href) => {
            const linkElement = screen.getByText(text).closest('a');
            expect(linkElement).toBeInTheDocument();
            expect(linkElement).toHaveAttribute('href', href);
        };

        // Check for specific links/buttons
        expectLink('New Invoice', '/admin/invoices/create?user=1');
        expectLink('Receive Money', '/admin/transactions/create?user=1&type=receive');
        expectLink('Send Money', '/admin/transactions/create?user=1&type=send-money');
        expectLink('Refund Money', '/admin/transactions/create?user=1&type=refund');
        expectLink('Invoices', '/admin/invoices?user=1');
        expectLink('Swap Budgets', '/admin/transactions/transfer?user=1');
        expectLink('All Transactions', '/admin/transactions?user=1');
        
        expectLink('Projects', '/admin/projects?user_id=1');
        expectLink('Assign Task', '/admin/users/1/tasks/add');
        expectLink('Notes', '/admin/users/1/notes');
        expectLink('User Files', '/admin/users/1/files');
        expectLink('View Profile', '/admin/users/1');
        expectLink('Edit Client', '/admin/users/1/edit');

        // These are actual buttons with onClick handlers, not links
        expect(screen.getByText('Login As').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Reset Password').closest('button')).toBeInTheDocument();
    });

    it('calls onLoginAs when Login As button is clicked', () => {
        const onLoginAsMock = vi.fn();
        render(
            <ClientActionsSheet
                client={mockClient}
                isOpen={true}
                onClose={vi.fn()}
                onLoginAs={onLoginAsMock}
                onResetPassword={vi.fn()}
            />
        );

        fireEvent.click(screen.getByText('Login As'));
        expect(onLoginAsMock).toHaveBeenCalledWith(1);
    });

    it('calls onResetPassword when Reset Password button is clicked', () => {
        const onResetPasswordMock = vi.fn();
        render(
            <ClientActionsSheet
                client={mockClient}
                isOpen={true}
                onClose={vi.fn()}
                onLoginAs={vi.fn()}
                onResetPassword={onResetPasswordMock}
            />
        );

        fireEvent.click(screen.getByText('Reset Password'));
        expect(onResetPasswordMock).toHaveBeenCalledWith(1);
    });
});
