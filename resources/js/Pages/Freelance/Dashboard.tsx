import React from 'react';
import FreelanceLayout from './Layout';
import { Head } from '@inertiajs/react';

export default function FreelanceDashboard({ auth }: any) {
    return (
        <FreelanceLayout auth={auth}>
            <Head title="Freelance Dashboard" />
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 className="text-lg font-bold mb-4">Freelance Portal</h3>
                <p>Find jobs, submit proposals, manage contracts.</p>
            </div>
        </FreelanceLayout>
    );
}
