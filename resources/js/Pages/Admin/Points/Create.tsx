import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ArrowDownLeft, Coins, ArrowUpRight } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Props {
    client: {
        id: number;
        name: string;
        email: string;
        points_balance: number;
    };
}

export default function Create({ client }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        reason: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('points.adjust', client.id), {
            onSuccess: () => {
                reset();
            }
        });
    };

    const { auth } = usePage().props as any;

    return (
        <AdminSidebarLayout title={__('general.add_points')} header={__('general.add_points')} user={auth?.user}>
            <Head title={`${__('general.add_points')}: ${client.name}`} />

            <div className="w-full max-w-7xl mx-auto py-6 space-y-6">
                <header className="mb-4">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-semibold px-2 py-1 bg-primary/10 text-primary rounded-md uppercase tracking-wider">
                            Finance
                        </span>
                    </div>
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{__('general.add_points')}</h1>
                            <p className="text-muted-foreground mt-1">
                                {client.name} ({client.email})
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" onClick={() => window.history.back()}>
                                <ArrowDownLeft className="h-4 w-4 me-2" style={{ transform: 'rotate(45deg)' }} /> Back
                            </Button>
                        </div>
                    </div>
                </header>

                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    {/* Stats Sidebar */}
                    <aside className="xl:col-span-3 space-y-4">
                        <Card>
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                                    <Coins className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Current Points</p>
                                    <p className="font-bold text-xl truncate">
                                        {client.points_balance}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </aside>

                    {/* Main Form Area */}
                    <div className="xl:col-span-9">
                        <Card>
                            <form onSubmit={handleSubmit}>
                                <CardHeader>
                                    <CardTitle>Adjust Points Balance</CardTitle>
                                    <CardDescription>
                                        Add positive values to credit points, or negative values to deduct points.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Number of Points</Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            value={data.amount}
                                            onChange={e => setData('amount', e.target.value)}
                                            placeholder="e.g. 500 or -100"
                                            autoFocus
                                        />
                                        {errors.amount && <div className="text-sm text-red-500">{errors.amount}</div>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="reason">Reason / Description</Label>
                                        <Input
                                            id="reason"
                                            type="text"
                                            value={data.reason}
                                            onChange={e => setData('reason', e.target.value)}
                                            placeholder="e.g. Bonus for completing a task"
                                        />
                                        {errors.reason && <div className="text-sm text-red-500">{errors.reason}</div>}
                                    </div>
                                </CardContent>
                                <CardFooter className="bg-muted/50 flex justify-end">
                                    <Button type="submit" disabled={processing} className="min-w-[150px]">
                                        <ArrowUpRight className="h-4 w-4 me-2" />
                                        {processing ? 'Processing...' : __('general.submit')}
                                    </Button>
                                </CardFooter>
                            </form>
                        </Card>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
