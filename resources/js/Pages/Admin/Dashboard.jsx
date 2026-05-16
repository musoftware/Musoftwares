import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from "@/Layouts/AdminLayout";
import { StatCard } from '@/Components/StatCard';
import { DollarSign, Building2, Users, ArrowDownCircle } from 'lucide-react';
import { LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer } from 'recharts';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

// Mock data for charts
const revenueData = [
  { name: 'Jan', income: 4000, expenses: 2400 },
  { name: 'Feb', income: 3000, expenses: 1398 },
  { name: 'Mar', income: 2000, expenses: 9800 },
  { name: 'Apr', income: 2780, expenses: 3908 },
  { name: 'May', income: 1890, expenses: 4800 },
  { name: 'Jun', income: 2390, expenses: 3800 },
  { name: 'Jul', income: 3490, expenses: 4300 },
  { name: 'Aug', income: 4490, expenses: 4300 },
  { name: 'Sep', income: 5490, expenses: 4300 },
  { name: 'Oct', income: 6490, expenses: 4300 },
  { name: 'Nov', income: 7490, expenses: 4300 },
  { name: 'Dec', income: 8490, expenses: 4300 },
];

const moduleData = [
  { name: 'ERP Invoices', value: 400, color: '#4f46e5' }, // indigo
  { name: 'Marketplace', value: 300, color: '#06b6d4' }, // cyan
  { name: 'Subscriptions', value: 300, color: '#22c55e' }, // green
  { name: 'Points', value: 200, color: '#eab308' }, // yellow
];

export default function Dashboard({ stats, recentInvoices, recentOrders, recentWithdrawals, auth }) {
    // We use stats, recentOrders to avoid unused vars
    console.log(stats, recentOrders);

    return (
        <AdminLayout user={auth?.user}>
            <Head title="Admin Dashboard" />
            <div className="space-y-6 pb-20">
                <div className="flex justify-between items-center">
                    <h1 className="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
                </div>

                {/* TOP STATS ROW (4 cards) */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard
                        title="Total Revenue"
                        value="$48,250 USD"
                        subtitle="↑ 12% this month"
                        icon={DollarSign}
                        trend="up"
                    />
                    <StatCard
                        title="Active Tenants"
                        value="24 tenants"
                        subtitle="↑ 3 new"
                        icon={Building2}
                        trend="up"
                    />
                    <StatCard
                        title="Active Clients"
                        value="312 clients"
                        subtitle="↑ 18 new"
                        icon={Users}
                        trend="up"
                    />
                    <StatCard
                        title="Pending Withdrawals"
                        value="5 requests"
                        subtitle="$2,400 pending"
                        icon={ArrowDownCircle}
                        trend="neutral"
                    />
                </div>

                {/* SECOND ROW — Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    {/* Revenue Chart (60% -> 3/5 cols) */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>Revenue Chart</CardTitle>
                        </CardHeader>
                        <CardContent className="pl-0">
                            <div className="h-[300px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={revenueData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                        <XAxis dataKey="name" axisLine={false} tickLine={false} />
                                        <YAxis axisLine={false} tickLine={false} tickFormatter={(value) => `$${value}`} />
                                        <RechartsTooltip formatter={(value) => [`$${value}`, undefined]} />
                                        <Legend />
                                        <Line type="monotone" dataKey="income" name="Income" stroke="#4f46e5" strokeWidth={2} activeDot={{ r: 8 }} />
                                        <Line type="monotone" dataKey="expenses" name="Expenses" stroke="#ef4444" strokeWidth={2} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Module Breakdown (40% -> 2/5 cols) */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Module Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[300px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={moduleData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={80}
                                            paddingAngle={5}
                                            dataKey="value"
                                        >
                                            {moduleData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip />
                                        <Legend verticalAlign="bottom" height={36} formatter={(value, entry) => <span className="text-sm text-gray-700">{value} ({entry.payload.value})</span>}/>
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* THIRD ROW — 3 columns */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Col 1: Recent Invoices */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-lg">Recent Invoices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(recentInvoices || []).slice(0, 5).map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">{invoice.client?.name || 'Unknown'}</TableCell>
                                            <TableCell>
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: invoice.currency_code || 'USD' }).format(invoice.total)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={invoice.status === 'Paid' ? 'default' : 'secondary'}>
                                                    {invoice.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {(!recentInvoices || recentInvoices.length === 0) && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No recent invoices</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Link href="#" className="text-sm text-blue-600 hover:underline">View all →</Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Col 2: Recent Withdrawals */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Recent Withdrawals</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(recentWithdrawals || []).slice(0, 5).map((withdrawal) => (
                                        <TableRow key={withdrawal.id}>
                                            <TableCell className="font-medium">Wallet {withdrawal.wallet_id}</TableCell>
                                            <TableCell>
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(withdrawal.amount)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Badge variant={withdrawal.status === 'Pending' ? 'secondary' : 'default'}>
                                                        {withdrawal.status || 'Pending'}
                                                    </Badge>
                                                    {(!withdrawal.status || withdrawal.status === 'Pending') && (
                                                        <Button variant="outline" size="sm" className="h-6 text-xs px-2" asChild>
                                                            <Link href="#">Review →</Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {(!recentWithdrawals || recentWithdrawals.length === 0) && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No recent withdrawals</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Col 3: New Tenants */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">New Tenants</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {/* Dummy data for tenants as it's not in props */}
                                {[
                                    { id: 1, name: 'Acme Corp', plan: 'Pro', date: '2 days ago', initials: 'AC' },
                                    { id: 2, name: 'Globex Inc', plan: 'Basic', date: '3 days ago', initials: 'GI' },
                                    { id: 3, name: 'Initech', plan: 'Enterprise', date: '1 week ago', initials: 'IN' },
                                ].map((tenant) => (
                                    <div key={tenant.id} className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Avatar>
                                                <AvatarFallback>{tenant.initials}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p className="text-sm font-medium leading-none">{tenant.name}</p>
                                                <p className="text-xs text-muted-foreground">{tenant.date}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Badge variant="outline">{tenant.plan}</Badge>
                                            <Link href="#" className="text-sm text-blue-600 hover:underline">View →</Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* QUICK ACTIONS (floating bar at bottom) */}
            <div className="fixed bottom-0 left-0 right-0 md:left-64 bg-white/80 backdrop-blur-md border-t border-gray-200 p-4 z-10 flex justify-center shadow-lg">
                <div className="flex space-x-4">
                    <Button variant="default" asChild>
                        <Link href="#">+ New Invoice</Link>
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link href="#">+ New Client</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/admin/reports/pnl">📊 Run Report</Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href="#">⚙️ Settings</Link>
                    </Button>
                </div>
            </div>
        </AdminLayout>
    );
}
