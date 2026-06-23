import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import LeadCard from '../LeadCard';
import { router } from '@inertiajs/react';

// Mock inertia router
vi.mock('@inertiajs/react', () => ({
    router: {
        visit: vi.fn(),
    },
}));

// Mock Drag and Drop Context
vi.mock('@hello-pangea/dnd', () => ({
    Draggable: ({ children }: any) => children({
        innerRef: vi.fn(),
        draggableProps: {},
        dragHandleProps: {},
    }, { isDragging: false }),
}));

// Mock window.route if using Ziggy
(window as any).route = vi.fn().mockImplementation((name: string, id: number) => `/${name}/${id}`);

describe('LeadCard Component', () => {
    it('renders lead information and handles click via router.visit', () => {
        const mockLead = {
            id: 123,
            name: 'John Doe',
            source: 'Website',
            score: 85,
            slaBreached: false,
            stageId: 'new',
            order: 0,
            value: 1000
        };

        const { container } = render(<LeadCard lead={mockLead} index={0} />);
        
        expect(screen.getByText('John Doe')).toBeDefined();
        expect(screen.getByText('Website')).toBeDefined();
        
        fireEvent.click(container.firstChild as Element);
        
        expect(router.visit).toHaveBeenCalledWith('/crm.leads.show/123');
    });
});
