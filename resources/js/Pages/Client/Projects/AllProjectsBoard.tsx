import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import ClientPage, { TextAction } from '@/Components/Client/ClientPage';
import ProjectBoard, { type BoardCard } from './Components/ProjectBoard';
import CalendarSelector from '@/Components/CalendarSelector';
import { __ } from '@/lib/i18n';

interface Props {
    date: string;
    lanes: string[];
    cards: BoardCard[];
    categories: any[];
    isAdmin?: boolean;
    projects: { id: number; name: string }[];
}

/** One day of work across all the client's projects; move between days or pick a date. */
export default function AllProjectsBoard({ date, lanes, cards, categories, projects = [] }: Props) {
    const { locale } = usePage().props as any;
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const [calendarOpen, setCalendarOpen] = useState(false);

    const goToDate = (target: string) => {
        if (!target || target === date) return;
        router.visit(route('client.projects.all-projects-board.date', { date: target }), { preserveScroll: true });
    };
    const dayLabel = day.toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const navBtn = 'inline-flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-sm hover:bg-[#f5f5f7] dark:bg-zinc-900';

    return (
        <ClientPage title={__('client_work.board.title')} hint={__('client_work.board.hint')} width="lg">
            <div className="flex flex-wrap items-center gap-4 text-sm">
                <TextAction href={route('client.projects.index')}>{__('client_work.projects.list')}</TextAction>
                <span className="font-semibold">{__('client_work.projects.board')}</span>
                <TextAction href={route('client.projects.all-tasks')}>{__('client_work.projects.all_tasks')}</TextAction>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <button onClick={() => goToDate(prev)} className={navBtn} title={__('client_work.board.prev')} aria-label={__('client_work.board.prev')}>
                    <ChevronLeft className="h-5 w-5 rtl:rotate-180" />
                </button>
                <button onClick={() => setCalendarOpen(true)} className="inline-flex h-11 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold shadow-sm hover:bg-[#f5f5f7] dark:bg-zinc-900">
                    <CalendarIcon className="h-4 w-4 text-[#0071e3]" />{dayLabel}
                </button>
                <button onClick={() => goToDate(next)} className={navBtn} title={__('client_work.board.next')} aria-label={__('client_work.board.next')}>
                    <ChevronRight className="h-5 w-5 rtl:rotate-180" />
                </button>
                {date !== todayStr && <TextAction onClick={() => goToDate(todayStr)}>{__('client_work.board.today')}</TextAction>}
            </div>

            <CalendarSelector
                open={calendarOpen}
                onOpenChange={setCalendarOpen}
                activeDates={[]}
                selectedDate={date}
                onSelectDate={(d) => { goToDate(d); setCalendarOpen(false); }}
            />

            <ProjectBoard
                projectId="all"
                date={date}
                lanes={lanes}
                initialCards={cards}
                categories={categories}
                isConsolidated={true}
                projects={projects}
                hideFuture={false}
                readOnly={false}
            />
        </ClientPage>
    );
}
