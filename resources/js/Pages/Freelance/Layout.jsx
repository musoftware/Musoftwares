import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { FreelanceModeProvider, useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import FreelanceModeToggle from '@/Components/Freelance/FreelanceModeToggle';
import { Search, Plus } from 'lucide-react';

function LayoutContent({ auth, children, clean = false }) {
    const { mode } = useFreelanceMode();

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Freelance" />

            <div className="max-w-[1600px] mx-auto">
                {/* Modern Sleek Workspace Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 mb-6 border-b border-slate-200/60">
                    <div className="flex items-center gap-3">
                        <span className="text-sm font-semibold text-slate-800 tracking-tight">Freelance Hub</span>
                        <span className="text-slate-300">/</span>
                        <FreelanceModeToggle />
                    </div>

                    <div className="flex items-center gap-2">
                        {mode === 'client' && (
                            <>
                                <Link 
                                    href={route('freelance.my-jobs')} 
                                    className="text-xs font-medium text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-md hover:bg-slate-100 transition-colors"
                                >
                                    My Jobs
                                </Link>
                                <Link 
                                    href={route('freelance.jobs.create')} 
                                    className="text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-md shadow-sm transition-colors flex items-center gap-1"
                                >
                                    <Plus className="h-3.5 w-3.5" /> Post a Job
                                </Link>
                            </>
                        )}

                        {mode === 'freelancer' && (
                            <>
                                <Link 
                                    href={route('freelance.jobs.browse')} 
                                    className="text-xs font-semibold bg-slate-950 hover:bg-slate-900 text-white px-3 py-1.5 rounded-md shadow-sm transition-colors flex items-center gap-1.5"
                                >
                                    <Search className="h-3.5 w-3.5 text-slate-300" /> Browse Jobs
                                </Link>
                            </>
                        )}
                    </div>
                </div>

                {/* Main Content Area */}
                {clean ? (
                    <div>
                        {children}
                    </div>
                ) : (
                    <div className="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                        {children}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

export default function FreelanceLayout({ auth, children, clean = false }) {
    return (
        <FreelanceModeProvider>
            <LayoutContent auth={auth} clean={clean}>
                {children}
            </LayoutContent>
        </FreelanceModeProvider>
    );
}

