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
