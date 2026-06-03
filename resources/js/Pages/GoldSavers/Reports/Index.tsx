import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { FileText, Download, TrendingUp } from 'lucide-react';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import GoldSaversTabs from '../Components/GoldSaversTabs';

interface ReportsProps {
    hasReports: boolean;
    wallets: any[];
}

export default function ReportsIndex({ hasReports, wallets }: ReportsProps) {
    const { data, setData, post, processing } = useForm({
        wallet_id: 'all',
        period: 'monthly'
    });

    const handleDownload = (e: React.FormEvent) => {
        e.preventDefault();
        window.location.href = route('isaas.gold-savers.reports.download', {
            wallet_id: data.wallet_id,
            period: data.period
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        {__('Investment Reports')}
                    </h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('Investment Reports')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    <div className="relative">
                        {!hasReports && (
                            <UpgradeOverlay 
                                module="gold-investment-reports"
                                title={__('Investment Reports Locked')}
                                description={__('Unlock the ability to download comprehensive PDF reports of your gold investments, performance metrics, and historical growth.')}
                                icon={TrendingUp}
                                priceText={__('general.upgrade_now')}
                            />
                        )}
                        
                        <div className={!hasReports ? "blur-md pointer-events-none select-none" : ""}>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="h-5 w-5 text-indigo-600" />
                                        {__('Generate Report')}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('Download a PDF summary of your gold savings and performance.')}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleDownload} className="space-y-6">
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">{__('Select Wallet')}</label>
                                                <Select value={data.wallet_id} onValueChange={(val) => setData('wallet_id', val || '')}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={__('Select Wallet')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all">{__('All Wallets')}</SelectItem>
                                                        {wallets.map((w: any) => (
                                                            <SelectItem key={w.id} value={w.id.toString()}>
                                                                {w.name} ({w.balance_grams} {__('g')})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">{__('Select Period')}</label>
                                                <Select value={data.period} onValueChange={(val) => setData('period', val || '')}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={__('Select Period')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="monthly">{__('Monthly')}</SelectItem>
                                                        <SelectItem value="yearly">{__('Yearly')}</SelectItem>
                                                        <SelectItem value="all_time">{__('All Time')}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                            <Download className="h-4 w-4 mr-2" />
                                            {__('Generate Report')}
                                        </Button>

                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
