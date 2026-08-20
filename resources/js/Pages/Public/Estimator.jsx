import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import ProjectEstimator from '@/Components/Estimator/ProjectEstimator';

export default function Estimator({ exchangeRate = 50.0 }) {
    return (
        <PublicLayout>
            <Head>
                <title>Project Cost & Budget Estimator - Multi-Platform Software Pricing | Musoftwares</title>
                <meta name="description" content="Calculate your website, desktop software, or mobile app development budget with AI, e-invoicing, payments, and transparent unit pricing." />
            </Head>

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans min-h-screen pt-16 pb-28 selection:bg-[#748660] selection:text-white px-6 sm:px-10">
                <ProjectEstimator exchangeRate={exchangeRate} showHeader={true} />
            </div>
        </PublicLayout>
    );
}
