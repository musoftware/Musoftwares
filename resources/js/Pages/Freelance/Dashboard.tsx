import { Head } from '@inertiajs/react';
import FreelanceLayout from './Layout';

export default function FreelanceDashboard({ auth }: any) {
    return (
        <FreelanceLayout auth={auth}>
            <Head title="Freelance Dashboard" />
            <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 className="mb-4 text-lg font-bold">Freelance Portal</h3>
                <p>Find jobs, submit proposals, manage contracts.</p>
            </div>
        </FreelanceLayout>
    );
}
