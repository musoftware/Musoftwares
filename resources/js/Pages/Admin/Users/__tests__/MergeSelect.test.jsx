import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { router } from '@inertiajs/react';
import MergeSelect from '../MergeSelect';

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }) => <title>{title}</title>,
    Link: ({ children, href, className }) => (
        <a href={href} className={className}>{children}</a>
    ),
    router: { get: vi.fn(), post: vi.fn() },
    usePage: () => ({ props: { auth: { user: { id: 1, name: 'Admin' } }, locale: 'en' } }),
}));

vi.mock('@/Layouts/AdminSidebarLayout', () => ({
    default: ({ children }) => <div data-testid="admin-layout">{children}</div>,
}));

describe('MergeSelect oldest-ID survivor election', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.route = (name, id) => {
            if (name === 'admin.users.merge.preview') return `/admin/users/${id}/merge`;
            if (name === 'admin.users.merge.select') return `/admin/users/${id}/merge-select`;
            if (name === 'admin.users.show') return `/admin/users/${id}`;
            return `/${name}/${id}`;
        };
    });

    it('promotes the older selected user ID as survivor when initiated from a newer user', () => {
        const newerSurvivor = {
            id: 912,
            name: 'Newer Duplicate',
            email: 'newer@example.com',
            role: 'client',
        };
        const olderSuggestion = {
            id: 150,
            name: 'Older Original',
            email: 'older@example.com',
            role: 'client',
            email_verified: true,
        };

        render(
            <MergeSelect
                survivor={newerSurvivor}
                search="older"
                suggestions={[olderSuggestion]}
                recently_merged={[]}
            />
        );

        const checkbox = screen.getByLabelText('older@example.com');
        fireEvent.click(checkbox);

        expect(screen.getAllByText('#150').length).toBeGreaterThanOrEqual(1);

        const continueBtn = screen.getByRole('button', { name: /continue/i });
        fireEvent.click(continueBtn);

        expect(router.get).toHaveBeenCalledWith('/admin/users/150/merge?duplicate_ids[]=912');
    });

    it('keeps the current user ID as survivor when merging a newer duplicate', () => {
        const olderSurvivor = {
            id: 150,
            name: 'Older Original',
            email: 'older@example.com',
            role: 'client',
        };
        const newerSuggestion = {
            id: 912,
            name: 'Newer Duplicate',
            email: 'newer@example.com',
            role: 'client',
            email_verified: false,
        };

        render(
            <MergeSelect
                survivor={olderSurvivor}
                search="newer"
                suggestions={[newerSuggestion]}
                recently_merged={[]}
            />
        );

        const checkbox = screen.getByLabelText('newer@example.com');
        fireEvent.click(checkbox);

        const continueBtn = screen.getByRole('button', { name: /continue/i });
        fireEvent.click(continueBtn);

        expect(router.get).toHaveBeenCalledWith('/admin/users/150/merge?duplicate_ids[]=912');
    });
});
