import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { ArrowLeft, TrendingUp, Users } from 'lucide-react';
import { __ } from '@/lib/i18n';


export default function Earnings({ earnings }: { earnings: any }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__("general.referral_earnings")} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 dashboard-container at-mobile-scroll-fix">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.referrals.index')} className="text-slate-400 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">{__("general.referral_earnings")}</h1>
                            <p className="text-slate-500 text-sm mt-1">{__("general.track_commissions_earned_from_referrals")}</p>
                        </div>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                        <CardTitle className="text-slate-900 text-sm font-semibold">{__("general.earnings_history")}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {earnings && earnings.data && (earnings.data as any).length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-start text-sm border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            <th className="px-6 py-3">{__("general.date")}</th>
                                            <th className="px-6 py-3">{__("erp.client_earner")}</th>
                                            <th className="px-6 py-3">{__("erp.referred_client")}</th>
                                            <th className="px-6 py-3 text-end">{__("general.amount")}</th>
                                            <th className="px-6 py-3 text-center">{__("general.status")}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 text-[13px] text-slate-600">
                                        {(earnings.data as any).map((earning: any) => (
                                            <tr key={earning.id} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {new Date(earning.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 font-medium text-slate-900">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                                                            <Users className="w-3 h-3 text-slate-500" />
                                                        </div>
                                                        {earning.client?.name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {earning.referred_client?.name}
                                                </td>
                                                <td className="px-6 py-4 text-end font-mono font-medium text-emerald-600">
                                                    <CurrencyDisplay amount={earning.amount} currency={earning.currency} />
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <StatusBadge status={earning.status} size="sm" />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <EmptyState 
                                icon={TrendingUp} 
                                title={__("general.no_earnings_recorded")} 
                                description={__("erp.commissions_will_appear_here_once")} 
                                className="border-0 rounded-none py-12" 
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
