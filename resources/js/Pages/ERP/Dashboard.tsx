import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function ERPDashboard({ stats }: { stats?: any }) {
    const [showTooltip, setShowTooltip] = useState(false);

    // Feature Discovery Tooltip
    useEffect(() => {
        const hasSeenTooltip = localStorage.getItem('erp_dashboard_tooltip_seen');
        if (!hasSeenTooltip) {
            setShowTooltip(true);
        }
    }, []);

    const dismissTooltip = () => {
        setShowTooltip(false);
        localStorage.setItem('erp_dashboard_tooltip_seen', 'true');
    };

    // First Time (No Data) State
    const hasData = stats && (stats.totalClients > 0 || stats.totalInvoices > 0);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 font-sora">
                    ERP Workspace
                </h2>
            }
        >
            <Head title="ERP Dashboard" />

            <div className="py-12 relative">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">

                    {!hasData ? (
                        /* Welcome State (No Data) */
                        <div className="bg-white p-8 rounded-[12px] shadow-lg border border-gray-100 max-w-3xl mx-auto text-center">
                            <h3 className="text-[24px] font-bold font-sora mb-6">Welcome to your ERP! 👋</h3>
                            <p className="text-gray-600 mb-8">Get started by setting up your essentials:</p>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-left max-w-md mx-auto">
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">1</div>
                                    <span className="font-medium">Add your first client</span>
                                </div>
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">2</div>
                                    <span className="font-medium">Create your first invoice</span>
                                </div>
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px] md:col-span-2">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">3</div>
                                    <span className="font-medium">Set up your bank account</span>
                                </div>
                            </div>

                            <div className="flex justify-center space-x-4">
                                <Link href="#" className="bg-indigo-600 text-white px-6 py-2 rounded-[8px] hover:bg-indigo-700 transition">
                                    Add Client
                                </Link>
                                <Link href="#" className="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-[8px] hover:bg-gray-50 transition">
                                    Create Invoice
                                </Link>
                            </div>
                        </div>
                    ) : (
                        /* Normal Dashboard State */
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-lg font-bold">
                                Enterprise Resource Planning
                            </h3>
                            <p>
                                Manage tenants, clients, invoices, and accounting.
                            </p>
                        </div>
                    )}

                    {/* Tooltip Overlay */}
                    {showTooltip && (
                        <div className="fixed bottom-8 right-8 z-50 animate-bounce">
                            <div className="bg-indigo-600 text-white p-4 rounded-[8px] shadow-lg relative">
                                <p className="font-medium pr-6">Start by creating an invoice →</p>
                                <button
                                    onClick={dismissTooltip}
                                    className="absolute top-2 right-2 text-indigo-200 hover:text-white"
                                >
                                    ✕
                                </button>
                                <div className="absolute -bottom-2 right-6 w-4 h-4 bg-indigo-600 transform rotate-45"></div>
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
