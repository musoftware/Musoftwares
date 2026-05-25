import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Index from '../Index';
import axios from 'axios';
import { router } from '@inertiajs/react';

// Mock inertia modules
vi.mock('@inertiajs/react', () => ({
    Head: ({ children }) => <>{children}</>,
    Link: ({ children, href, className }) => <a href={href} className={className}>{children}</a>,
    router: {
        get: vi.fn(),
    },
}));

// Mock axios
vi.mock('axios');

// Mock ResizeObserver which might be needed for Radix UI components
class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
}
window.ResizeObserver = ResizeObserver;

// Ensure window.confirm and window.alert are mocked
window.confirm = vi.fn();
window.alert = vi.fn();

// Mock Layout so we don't need to mount the whole sidebar and nav
vi.mock('@/Layouts/AdminSidebarLayout', () => ({
    default: ({ children, title }) => (
        <div data-testid="admin-sidebar-layout">
            <h1>{title}</h1>
            {children}
        </div>
    )
}));

// Provide a basic mock for DataTable to verify it receives correct props,
// or just let it render if it's lightweight. Since DataTable from '@/Components/ui/DataTable'
// might use react-table which is fine, let's allow it to render or partially mock it if it fails.
// Assuming DataTable renders fine in JSDOM.

describe('Clients Index', () => {
    const mockClients = {
        data: [
            {
                id: 1,
                name: 'John Doe',
                full_name: 'Johnathan Doe',
                email: 'john@example.com',
                phone_number: '1234567890',
                wallet: { balance: 100, currency: 'USD' }
            }
        ]
    };

    beforeEach(() => {
        vi.clearAllMocks();
        // Setup local storage mock for login as
        const localStorageMock = (function() {
            let store = {};
            return {
                getItem: function(key) { return store[key] || null; },
                setItem: function(key, value) { store[key] = value.toString(); },
                clear: function() { store = {}; }
            };
        })();
        Object.defineProperty(window, 'localStorage', { value: localStorageMock });
        delete window.location;
        window.location = { href: '' };
    });

    it('renders the data table with client data', () => {
        render(<Index clients={mockClients} filters={{}} />);
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();
    });

    it('opens ClientActionsSheet when client name is clicked', async () => {
        render(<Index clients={mockClients} filters={{}} />);
        
        // Click on client name to open sheet
        fireEvent.click(screen.getByText('John Doe'));
        
        // Check if sheet content appears (e.g. by checking for specific sheet buttons)
        expect(await screen.findByText('All Invoices')).toBeInTheDocument();
    });

    it('calls axios for login as from dropdown', async () => {
        axios.post.mockResolvedValueOnce({ data: { token: 'mock-token', redirect_url: '/dashboard' } });

        render(<Index clients={mockClients} filters={{}} />);
        
        // Open dropdown
        const menuButton = screen.getAllByRole('button', { name: /open menu/i })[0];
        fireEvent.click(menuButton);
        
        // Click Login As
        const loginAsMenuItem = await screen.findByText('Login As');
        fireEvent.click(loginAsMenuItem);
        
        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/admin/users/1/login-as');
        });
        
        // Also verify that window.location.href changed
        expect(window.location.href).toBe('/dashboard');
        expect(window.localStorage.getItem('auth_token')).toBe('mock-token');
    });

    it('calls axios for reset password from dropdown when confirmed', async () => {
        window.confirm.mockReturnValueOnce(true);
        axios.post.mockResolvedValueOnce({ data: { new_password: 'new-password-123' } });

        render(<Index clients={mockClients} filters={{}} />);
        
        // Open dropdown
        const menuButton = screen.getAllByRole('button', { name: /open menu/i })[0];
        fireEvent.click(menuButton);
        
        // Click Reset Password
        const resetPasswordMenuItem = await screen.findByText('Reset Password');
        fireEvent.click(resetPasswordMenuItem);
        
        expect(window.confirm).toHaveBeenCalledWith("Are you sure you want to reset this user's password?");
        
        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/admin/users/1/reset-password');
            expect(window.alert).toHaveBeenCalledWith("Password reset successfully.\nNew password: new-password-123");
        });
    });
});
