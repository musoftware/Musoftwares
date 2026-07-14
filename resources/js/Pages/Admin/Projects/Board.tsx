import React, { useCallback, useMemo, useState } from 'react';
import {
    CalendarDays, Clock, LayoutDashboard,
} from 'lucide-react';
import { router } from '@inertiajs/react';
import AdminBoardLayout from '@/Layouts/AdminBoardLayout';
import ProjectBoard, { type BoardCard, type BoardPreferences } from '@/Pages/Client/Projects/Components/ProjectBoard';
import BoardTopNav, { type BoardFilter } from './Components/BoardTopNav';
import BoardCategoriesManager, { type BoardCategory } from './Components/BoardCategoriesManager';
import ProjectAdminNotesSidebar, { type AdminNote } from './Components/ProjectAdminNotesSidebar';
import { NOTICES_MANAGER_OPEN_EVENT } from '@/Components/Admin/NoticesManager';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { formatMoney, formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import type { BoardProject } from '@/types/project';

interface Props {
    project: BoardProject;
    date: string;
    lanes: string[];
    cards: BoardCard[];
    activeDates: string[];
    categories?: BoardCategory[];
    preferences?: BoardPreferences;
    adminNotes: AdminNote[];
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    hold_on: 'bg-amber-100 text-amber-700 ring-amber-200',
    closed: 'bg-slate-200 text-slate-700 ring-slate-300',
};

const STATUS_LABEL_KEY: Record<string, string> = {
    open: 'general.status_open',
    hold_on: 'general.status_hold_on',
    closed: 'general.status_closed',
};

export default function AdminProjectBoard({ project, date, lanes, cards, activeDates, categories, preferences, adminNotes }: Props) {
    const day = parseISO(date);
    const [filter, setFilter] = useState<string>('all');
    const [showCategories, setShowCategories] = useState<boolean>(false);
    const [showAdminNotes, setShowAdminNotes] = useState<boolean>(false);
    const [adminNotesList, setAdminNotesList] = useState<AdminNote[]>(adminNotes || []);

    const triggerAddNote = useCallback(() => {
        const btn = document.querySelector<HTMLButtonElement>('[data-board-add-note]');
        if (btn) {
            btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => btn.click(), 220);
        }
    }, []);

    const handleAdd = useCallback(
        (kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'ai') => {
            // Handled via state trigger for modals in the child component
            const customEvent = new CustomEvent('board-add-trigger', { detail: { kind } });
            window.dispatchEvent(customEvent);
        },
        [],
    );

    const counts = useMemo(
        () => ({
            all: cards.length,
            backlog: cards.filter((c) => c.lane === 'backlog').length,
            in_progress: cards.filter((c) => c.lane === 'in_progress').length,
            review: cards.filter((c) => c.lane === 'review').length,
            done: cards.filter((c) => c.lane === 'done').length,
            note: cards.filter((c) => c.type === 'note').length,
            task: cards.filter((c) => c.type === 'task').length,
            report: cards.filter((c) => c.type === 'report').length,
            todo: cards.filter((c) => c.type === 'todo').length,
            file: cards.filter((c) => c.type === 'file').length,
        }),
        [cards],
    );

    return (
        <AdminBoardLayout
            title={`${project.name} · ${__('general.board')}`}
        >
            {/* Sticky custom top nav — uses same visual language as the Client Board */}
            <BoardTopNav
                project={{
                    id: project.id,
                    name: project.name,
                    status: project.status ?? undefined,
                    archived: project.archived,
                    share_url: project.share_url ?? undefined,
                    short_url: project.short_url ?? undefined,
                    client_name: project.client_name ?? undefined,
                }}
                activeFilter={filter as any}
                onFilterChange={setFilter}
                counts={counts}
                date={date}
                onAdd={handleAdd}
                activeDates={activeDates}
                onManageCategories={() => setShowCategories(true)}
                onManageNotices={() => window.dispatchEvent(new CustomEvent(NOTICES_MANAGER_OPEN_EVENT))}
                onToggleAdminNotes={() => setShowAdminNotes(prev => !prev)}
                adminNotesOpen={showAdminNotes}
            />

            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8 max-w-none">
                {/* The shared per-day board, controlled by the top-nav filter */}
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                    externalFilter={filter as any}
                    categories={categories}
                    preferences={preferences}
                />

                <p className="text-center text-xs text-slate-400">
                    {__('general.board_persistence_hint')}
                </p>
            </div>

            {categories && (
                <BoardCategoriesManager
                    projectId={project.id}
                    open={showCategories}
                    onClose={() => setShowCategories(false)}
                    initialCategories={categories}
                />
            )}

            <ProjectAdminNotesSidebar
                projectId={project.id}
                open={showAdminNotes}
                onClose={() => setShowAdminNotes(false)}
                notes={adminNotesList}
                onNotesChange={setAdminNotesList}
                boardCategories={categories}
            />
        </AdminBoardLayout>
    );
}
