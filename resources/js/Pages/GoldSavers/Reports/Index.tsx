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
                        {__('general.investment_reports')}
                    </h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('general.investment_reports')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    <div className="relative">
                        {!hasReports && (
                            <UpgradeOverlay 
                                module="gold-investment-reports"
                                title={__('general.investment_reports_locked')}
                                description={__('gold_saver.unlock_the_ability_to_download')}
                                icon={TrendingUp}
                                priceText={__('general.upgrade_now')}
                            />
                        )}
                        
                        <div className={!hasReports ? "blur-md pointer-events-none select-none" : ""}>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="h-5 w-5 text-indigo-600" />
                                        {__('general.generate_report')}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('gold_saver.download_a_pdf_summary_of')}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleDownload} className="space-y-6">
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">{__('erp.select_wallet')}</label>
                                                <Select value={data.wallet_id} onValueChange={(val) => setData('wallet_id', val || '')}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={__('erp.select_wallet')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all">{__('erp.all_wallets')}</SelectItem>
                                                        {wallets.map((w: any) => (
                                                            <SelectItem key={w.id} value={w.id.toString()}>
                                                                {w.name} ({w.balance_grams} {__('general.g_2')})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">{__('general.select_period')}</label>
                                                <Select value={data.period} onValueChange={(val) => setData('period', val || '')}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={__('general.select_period')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="monthly">{__('general.monthly')}</SelectItem>
                                                        <SelectItem value="yearly">{__('general.yearly')}</SelectItem>
                                                        <SelectItem value="all_time">{__('general.all_time')}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                            <Download className="h-4 w-4 me-2" />
                                            {__('general.generate_report')}
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
