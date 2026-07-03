import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ClientActionsSheet from '../ClientActionsSheet';

// Mock Inertia Link + router
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, className, target, rel }) => (
        <a href={href} className={className} target={target} rel={rel}>{children}</a>
    ),
    router: { post: vi.fn(), get: vi.fn(), delete: vi.fn() },
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

        // Finance links
        expectLink('New Invoice', '/admin/invoices/create?user=1');
        expectLink('Create Recurring Invoice', '/admin/business/recurring/invoices/create?user=1');
        expectLink('Recurring Payout', '/admin/business/recurring/salaries?action=create&user=1');
        expectLink('New Payout', '/admin/payouts/create?user=1');
        expectLink('Invoices', '/admin/invoices?client_id=1');
        expectLink('Receive Money', '/admin/transactions/create?user=1&type=receive');
        expectLink('Send Money', '/admin/transactions/create?user=1&type=send-money');
        expectLink('Refund Money', '/admin/transactions/create?user=1&type=refund');
        expectLink('Swap Projects Budget', '/admin/transactions/transfer?user=1');
        expectLink('All Transactions', '/admin/transactions?user=1');

        // Workspace & Tools
        expectLink('Projects', '/admin/projects?user_id=1');
        expectLink('Assign Task', '/admin/users/1/tasks/add');
        expectLink('Files', '/admin/users/1/files');
        expectLink('Reports', '/admin/users/1/reports');
        expectLink('Manage Referrals', '/admin/users/1/referrals');

        // Buttons (handlers / dynamic)
        expect(screen.getByText('Login As').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Reset Password').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Change Role').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Recalc Balance').closest('button')).toBeInTheDocument();
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