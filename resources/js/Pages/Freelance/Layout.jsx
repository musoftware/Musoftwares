import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function FreelanceLayout({ auth, children }) {
    const [mode, setMode] = useState('client'); // 'client' or 'freelancer'

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Freelance" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Mode Switcher and Nav */}
                    <div className="mb-6 flex justify-between items-center bg-white p-4 rounded-lg shadow">
                        <div className="flex space-x-4">
                            <button
                                onClick={() => setMode('client')}
                                className={`px-4 py-2 rounded ${mode === 'client' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                            >
                                👤 Client Mode
                            </button>
                            <button
                                onClick={() => setMode('freelancer')}
                                className={`px-4 py-2 rounded ${mode === 'freelancer' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                            >
                                💻 Freelancer Mode
                            </button>
                        </div>

                        <div className="flex space-x-4 items-center">
                            <Link href={route('freelance.points.index')} className="text-gray-600 hover:text-gray-900">
                                💰 Points: {auth.user.points_balance || 0}
                            </Link>

                            {mode === 'client' && (
                                <>
                                    <Link href={route('freelance.my-jobs')} className="text-blue-600 hover:underline">My Jobs</Link>
                                    <Link href={route('freelance.jobs.create')} className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Post a Job</Link>
                                </>
                            )}

                            {mode === 'freelancer' && (
                                <>
                                    <Link href={route('freelance.jobs.browse')} className="text-green-600 hover:underline">Browse Jobs</Link>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Main Content Area */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        {children}
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
