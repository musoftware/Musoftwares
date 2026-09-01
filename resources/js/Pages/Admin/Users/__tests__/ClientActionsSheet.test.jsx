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

        // Profile & Security links
        expect(screen.getAllByText('View profile').length).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Edit profile').closest('a')).toHaveAttribute('href', '/admin/users/1/edit');
        expect(screen.getByText('User Subscriptions').closest('a')).toHaveAttribute('href', '/admin/users/1#subscriptions');
        expect(screen.getByText('Manage email aliases & merge').closest('a')).toHaveAttribute('href', '/admin/users/1/emails');

        // Finance links
        expectLink('New invoice', '/admin/invoices/create?user=1');
        expectLink('Create a Recurring Invoice', '/admin/business/recurring/invoices/create?user=1');
        expectLink('Recurring Payout', '/admin/business/recurring/salaries?action=create&user=1');
        expectLink('New Payout', '/admin/payouts/create?user=1');
        expectLink('Invoices', '/admin/invoices?client_id=1');
        expectLink('Receive Money', '/admin/transactions/create?user=1&type=receive');
        expectLink('Send Money', '/admin/transactions/create?user=1&type=send-money');
        expectLink('Refund Money', '/admin/transactions/create?user=1&type=refund');
        expectLink('Swap projects Budget', '/admin/transactions/transfer?user=1');
        expectLink('All transactions', '/admin/transactions?user=1');

        // Workspace & Tools
        expectLink('Projects', '/admin/projects?user_id=1');
        expectLink('Set Task', '/admin/users/1/tasks/add');
        expectLink('Files', '/admin/users/1/files');
        expectLink('Reports', '/admin/users/1/reports');
        expectLink('Manage Referrals', '/admin/users/1/referrals');
        expectLink('Due Balance Sheet', '/admin/users/1/balance-sheet');
        expectLink('Add Points', '/admin/points_controller/1/add');

        // Buttons (handlers / dynamic)
        expect(screen.getByText('Login As').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Reset password').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Change role').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Activate Membership').closest('button')).toBeInTheDocument();
        expect(screen.getByText('Recalc balance').closest('button')).toBeInTheDocument();
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

    it('calls onResetPassword when Reset password button is clicked', () => {
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

        fireEvent.click(screen.getByText('Reset password'));
        expect(onResetPasswordMock).toHaveBeenCalledWith(1);
    });
});