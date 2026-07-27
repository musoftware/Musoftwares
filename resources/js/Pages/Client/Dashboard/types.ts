export interface Currency {
    id: number;
    currency: string;
    symbol: string;
    [key: string]: any;
}

export interface ChartData {
    month: string;
    deposit: number;
    expense: number;
}

export interface DashboardStats {
    walletBalance: number;
    earnedBalance: number;
    pointsBalance: number;
    unpaidInvoices: number;
    unpaidAmount: number;
    outstandingBalance: number;
    activeSubscriptions: number;
    totalMonthlySubscription: number;
    openTickets: number;
    pendingWithdrawals: number;
    currency: Currency;
}


export interface RecentTransaction {
    id: string;
    date: string;
    type: string;
    amount: number;
    method: string;
    currency: Currency | null;
}

export interface UserProject {
    id: number;
    name: string;
    status: string;
    total_tasks: number;
    completed_tasks: number;
    progress: number;
    reports_count: number;
    files_count: number;
    updated_at: string;
}

export interface ActiveTool {
    id: number;
    tool_slug: string;
    tool_name: string;
    expires_at: string | null;
    status: string;
}

