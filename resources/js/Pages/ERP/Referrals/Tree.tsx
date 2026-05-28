import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { ArrowLeft, Network, Users } from 'lucide-react';

const __ = (key: string) => key;

function TreeNode({ client }: { client: any }) {
    return (
        <div className="mt-3 ml-6 border-l-2 border-slate-100 pl-4 relative">
            <div className="absolute w-4 h-0.5 bg-slate-100 left-0 top-3"></div>
            <div className="flex items-center space-x-2">
                <div className="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center shrink-0 border border-slate-200">
                    <Users className="w-3 h-3 text-slate-500" />
                </div>
                <span className="font-medium text-slate-800 text-sm">{client.name}</span>
                <span className="text-xs text-slate-400">({client.email})</span>
            </div>
            {client.referrals && client.referrals.length > 0 && (
                <div className="ml-2 mt-2">
                    {client.referrals.map((child: any) => (
                        <TreeNode key={child.id} client={child} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Tree({ client }: { client: any }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__("Referral Tree") + ` — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 dashboard-container at-mobile-scroll-fix">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.referrals.index')} className="text-slate-400 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">{__("Referral Tree")}</h1>
                            <p className="text-slate-500 text-sm mt-1">{__("Visualize the downstream referral network for")} <span className="font-semibold text-slate-700">{client.name}</span></p>
                        </div>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                        <CardTitle className="text-slate-900 text-sm font-semibold flex items-center gap-2">
                            <Network className="w-4 h-4 text-slate-500" />
                            {client.name}{__("'s Network")}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="bg-slate-50 border border-slate-100 rounded-lg p-6">
                            <div className="flex items-center space-x-3 mb-2">
                                <div className="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100">
                                    <Users className="w-4 h-4 text-indigo-600" />
                                </div>
                                <div>
                                    <h3 className="text-base font-bold text-slate-900">{client.name}</h3>
                                    <p className="text-xs text-slate-500">{client.email}</p>
                                </div>
                            </div>
                            
                            {client.referrals && client.referrals.length > 0 ? (
                                <div className="ml-4 mt-4">
                                    {client.referrals.map((child: any) => (
                                        <TreeNode key={child.id} client={child} />
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-6 text-slate-500 italic text-sm ml-11">
                                    {__("No referrals yet.")}
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
