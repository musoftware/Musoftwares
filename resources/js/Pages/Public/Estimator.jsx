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

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans min-h-screen pt-8 pb-24 selection:bg-[#0071e3]/20 selection:text-[#0071e3] px-6 sm:px-10">
                <ProjectEstimator exchangeRate={exchangeRate} showHeader={true} />
            </div>
        </PublicLayout>
    );
}
