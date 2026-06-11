import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { usePage } from '@inertiajs/react';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
} from '@/Components/ui/card';
import { 
    Users, 
    Briefcase, 
    FileText, 
    Activity,
    DollarSign,
    TrendingDown,
    TrendingUp
} from 'lucide-react';
import { formatCurrency } from '@/lib/utils';

export default function Reports() {
    const { stats } = usePage<any>().props;

    return (
        <AdminSidebarLayout 
            title="System Reports" 
            header="System Reports"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">Total Users</p>
                            <div className="p-2 bg-indigo-50 rounded-xl">
                                <Users className="h-4 w-4 text-indigo-600" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {stats.total_users}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">Total Projects</p>
                            <div className="p-2 bg-indigo-50 rounded-xl">
                                <Briefcase className="h-4 w-4 text-indigo-600" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {stats.total_projects}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">Total Invoices</p>
                            <div className="p-2 bg-indigo-50 rounded-xl">
                                <FileText className="h-4 w-4 text-indigo-600" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {stats.total_invoices}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between space-y-0 pb-2">
                            <p className="text-sm font-medium text-slate-500">Total Transactions</p>
                            <div className="p-2 bg-indigo-50 rounded-xl">
                                <Activity className="h-4 w-4 text-indigo-600" />
                            </div>
                        </div>
                        <div className="text-3xl font-bold text-slate-900 tracking-tight">
                            {stats.total_transactions}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2 border-b border-slate-50">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <DollarSign className="w-4 h-4" />
                            Lifetime Income
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="text-3xl font-bold text-emerald-600 tracking-tight">
                            {formatCurrency(stats.lifetime_income, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2 border-b border-slate-50">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <TrendingDown className="w-4 h-4 text-rose-500" />
                            Lifetime Expenses
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="text-3xl font-bold text-rose-600 tracking-tight">
                            {formatCurrency(stats.lifetime_expenses, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2 border-b border-slate-50">
                        <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <TrendingUp className="w-4 h-4 text-purple-500" />
                            Net Profit
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className={`text-3xl font-bold tracking-tight ${stats.net_profit >= 0 ? 'text-purple-600' : 'text-rose-600'}`}>
                            {formatCurrency(stats.net_profit, stats.business_currency_code)}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
