// Shared TypeScript types for the Admin Projects surface.
// Mirrors the JSON shape produced by App\Http\Resources\ProjectResource and
// the Inertia props passed to Admin/Projects/Index + Admin/Projects/Board.

export type ProjectStatus = 'open' | 'hold_on' | 'closed';

export interface ProjectClient {
    id: number;
    name: string;
    email: string;
}

export interface ProjectOwner {
    id: number;
    name: string;
    email: string;
}

export interface ProjectCounts {
    contracts: number;
    invoices_unpaid: number;
    tasks: number;
    reports: number;
    files: number;
}

export interface Project {
    id: number;
    user_id: number;
    owner_id: number | null;
    project_name: string;
    description: string | null;
    project_balance: string;
    budget: string;
    total_paid: string;
    hour_rate: string;
    percentage: number;
    status: ProjectStatus | null;
    archived: boolean;
    archived_at: string | null;
    hide_future_tasks: boolean;
    date_start: string | null;
    date_end: string | null;
    created_at: string | null;
    updated_at: string | null;
    client?: ProjectClient | null;
    owner?: ProjectOwner | null;
    currency?: {
        id: number;
        currency: string;
        symbol?: string;
        string_format?: string;
    } | null;
    counts?: ProjectCounts;
}

export interface ProjectPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface ProjectCollection {
    data: Project[];
    links?: ProjectPaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        sort: string | null;
        dir: 'asc' | 'desc';
        tab: string;
        status_filter: string | null;
    };
}

export interface BoardCounts {
    tasks: number;
    reports: number;
    files: number;
}

export interface BoardProject {
    id: number;
    name: string;
    description: string | null;
    status: string | null;
    archived: boolean;
    budget: string;
    project_balance: string;
    total_paid: string;
    hour_rate: string;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    client_name: string | null;
    owner_name: string | null;
    currency: {
        id: number;
        currency: string;
        symbol?: string;
        string_format?: string;
    } | null;
    counts: BoardCounts;
}

export type ProjectTab = 'active' | 'archived' | 'all';

export type ProjectSort = 'id' | 'project_name' | 'status' | 'created_at' | 'date_start' | 'date_end' | 'budget' | 'project_balance';

export interface ProjectsIndexProps {
    projects: ProjectCollection;
    currentTab: ProjectTab;
    statusFilter: ProjectStatus | null;
    sort: ProjectSort;
    dir: 'asc' | 'desc';
    perPage: number;
    perPageOptions: number[];
    filters: {
        search: string | null;
    };
}
