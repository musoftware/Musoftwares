import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { AlertCircle, AlertTriangle, Clock, TrendingUp } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { formatCurrency, formatDateHuman } from '@/lib/utils';
import { Badge } from '@/Components/ui/badge';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';

export default function CollectorDashboard({ stats, agingReport, highRiskAccounts, overdueInvoices }: any) {
    const baseCurrency = (typeof window !== 'undefined' && (window as any).currencies?.[0]) || 'USD';

    return (
        <CrmLayout title={__('general.collector_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.overdue_account_monitoring')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.manage_high_risk_accounts_and_overdue_invoices')}</p>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('general.total_overdue_amount')} 
                        value={formatCurrency(stats.total_overdue_amount, baseCurrency)} 
                        icon={TrendingUp} 
                        colorClass="bg-rose-100 text-rose-600" 
                    />
                    <KPICard 
                        title={__('general.total_overdue_invoices')} 
                        value={stats.total_overdue_invoices} 
                        icon={AlertCircle} 
                        colorClass="bg-orange-100 text-orange-600" 
                    />
                    <KPICard 
                        title={__('general.high_risk_accounts')} 
                        value={highRiskAccounts.length} 
                        icon={AlertTriangle} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('general.overdue_0_30_days')} 
                        value={formatCurrency(agingReport['0_30'], baseCurrency)} 
                        icon={Clock} 
                        colorClass="bg-yellow-100 text-yellow-600" 
                    />
                </div>

                {/* Aging Report summary */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="shadow-sm">
                        <CardHeader className="p-4 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">0-30 Days</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-xl font-bold">{formatCurrency(agingReport['0_30'], baseCurrency)}</div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm">
                        <CardHeader className="p-4 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">31-60 Days</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-xl font-bold">{formatCurrency(agingReport['31_60'], baseCurrency)}</div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm">
                        <CardHeader className="p-4 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">61-90 Days</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-xl font-bold">{formatCurrency(agingReport['61_90'], baseCurrency)}</div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-sm">
                        <CardHeader className="p-4 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">90+ Days</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 pt-0 text-red-600">
                            <div className="text-xl font-bold">{formatCurrency(agingReport['90_plus'], baseCurrency)}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 min-h-0">
                    {/* High Risk Accounts */}
                    <Card className="flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold text-red-600 flex items-center gap-2">
                                <AlertTriangle size={18} />
                                {__('general.high_risk_accounts')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-0 overflow-auto">
                            {highRiskAccounts.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('general.client')}</TableHead>
                                            <TableHead>{__('general.invoices')}</TableHead>
                                            <TableHead className="text-end">{__('general.total_overdue')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {highRiskAccounts.map((account: any, idx: number) => (
                                            <TableRow key={idx}>
                                                <TableCell>
                                                    <div className="font-medium">{account.client?.name || 'Unknown'}</div>
                                                    <div className="text-xs text-muted-foreground">{account.client?.phone || account.client?.email}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{account.invoices_count}</Badge>
                                                </TableCell>
                                                <TableCell className="text-end font-bold text-red-600">
                                                    {formatCurrency(account.total_overdue, account.currency || baseCurrency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="p-6 text-center text-sm text-muted-foreground">
                                    {__('general.no_high_risk_accounts')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Overdue Invoices List */}
                    <Card className="flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <Clock size={18} />
                                {__('general.recent_overdue_invoices')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-0 overflow-auto">
                            {overdueInvoices.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('general.invoice')}</TableHead>
                                            <TableHead>{__('general.due_date')}</TableHead>
                                            <TableHead className="text-end">{__('general.amount')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {overdueInvoices.map((invoice: any) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>
                                                    <div className="font-medium">#{invoice.invoice_number}</div>
                                                    <div className="text-xs text-muted-foreground">{invoice.client?.name}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-red-500 font-medium">
                                                        {formatDateHuman(invoice.due_date)}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-end font-bold">
                                                    {formatCurrency(Math.max(0, invoice.amount - invoice.paid_amount), invoice.currency || baseCurrency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="p-6 text-center text-sm text-muted-foreground">
                                    {__('general.no_overdue_invoices')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </CrmLayout>
    );
}
