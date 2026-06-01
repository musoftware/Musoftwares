import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Badge } from '@/Components/ui/badge';
import { Users, Link as LinkIcon, Network, TrendingUp, Copy, ExternalLink, ChevronRight } from 'lucide-react';
import { __ } from '@/lib/i18n';


export default function Index({ clients }: { clients: any[] }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__("Referrals")} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 dashboard-container at-mobile-scroll-fix">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__("Client Referrals")}</h1>
                        <p className="text-slate-500 text-sm mt-1">{__("Manage referral links and track client networks.")}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('erp.referrals.earnings')}
                            className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-slate-200 bg-white hover:bg-slate-100 hover:text-slate-900 h-9 px-4 py-2"
                        >
                            <TrendingUp className="mr-2 h-4 w-4" />
                            {__("View Earnings")}
                        </Link>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                        <CardTitle className="text-slate-900 text-sm font-semibold">{__("Clients & Referral Links")}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {clients && clients.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            <th className="px-6 py-3">{__("Client")}</th>
                                            <th className="px-6 py-3">{__("Referred By")}</th>
                                            <th className="px-6 py-3">{__("Referral Link")}</th>
                                            <th className="px-6 py-3 text-right">{__("Actions")}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 text-[13px] text-slate-600">
                                        {clients.map((client) => (
                                            <tr key={client.id} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4 font-medium text-slate-900">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                                                            <Users className="w-4 h-4 text-slate-500" />
                                                        </div>
                                                        {client.name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {client.referrer?.name ? (
                                                        <Badge variant="outline" className="text-slate-600 bg-slate-50">{client.referrer.name}</Badge>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {client.referral_code ? (
                                                        <div className="flex items-center gap-2 max-w-[200px] lg:max-w-xs">
                                                            <div className="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs truncate flex-1 text-slate-600 font-mono">
                                                                {`${window.location.origin}/register?ref=${client.referral_code}`}
                                                            </div>
                                                            <button 
                                                                onClick={() => navigator.clipboard.writeText(`${window.location.origin}/register?ref=${client.referral_code}`)}
                                                                className="p-1 text-slate-400 hover:text-indigo-600 transition-colors"
                                                                title={__("Copy Link")}
                                                            >
                                                                <Copy className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link
                                                        href={route('erp.referrals.tree', client.id)}
                                                        className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 font-medium text-xs transition-colors"
                                                    >
                                                        <Network className="w-3.5 h-3.5" />
                                                        {__("View Tree")}
                                                        <ChevronRight className="w-3 h-3" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <EmptyState 
                                icon={LinkIcon} 
                                title={__("No clients found")} 
                                description={__("Clients and their referral networks will appear here.")} 
                                className="border-0 rounded-none py-12" 
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
