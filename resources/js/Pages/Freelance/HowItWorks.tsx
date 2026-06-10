import React from 'react';
import PublicLayout from './PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { __ } from '@/lib/i18n';
import { Briefcase, UserPlus, CheckCircle2, CreditCard } from 'lucide-react';

export default function HowItWorks() {
    const steps = [
        {
            title: __('freelance.step_1_title', undefined, 'Create an Account'),
            description: __('freelance.step_1_desc', undefined, 'Sign up as a freelancer to offer your services, or as a client to post jobs.'),
            icon: UserPlus,
        },
        {
            title: __('freelance.step_2_title', undefined, 'Post or Find Work'),
            description: __('freelance.step_2_desc', undefined, 'Clients post detailed job requirements. Freelancers browse and submit proposals.'),
            icon: Briefcase,
        },
        {
            title: __('freelance.step_3_title', undefined, 'Collaborate'),
            description: __('freelance.step_3_desc', undefined, 'Agree on terms, sign a contract, and collaborate seamlessly through our platform.'),
            icon: CheckCircle2,
        },
        {
            title: __('freelance.step_4_title', undefined, 'Get Paid Securely'),
            description: __('freelance.step_4_desc', undefined, 'Payments are securely processed upon milestone completion and final delivery.'),
            icon: CreditCard,
        },
    ];

    return (
        <PublicLayout>
            <SeoHead 
                title={`${__('freelance.nav.how_it_works', undefined, 'How it Works')} - ${__('freelance.freelance')}`} 
                description={__('freelance.how_it_works_desc', undefined, 'Learn how ArabiJobs Freelance connects top talent with great businesses.')}
            />

            <div className="mx-auto max-w-3xl text-center pb-16">
                <h1 className="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                    {__('freelance.how_it_works_heading', undefined, 'How ArabiJobs Works')}
                </h1>
                <p className="mt-6 text-lg leading-8 text-slate-600">
                    {__('freelance.how_it_works_subheading', undefined, 'A simple, secure, and transparent way to hire talent or find freelance work.')}
                </p>
            </div>

            <div className="mx-auto max-w-5xl mt-8">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16">
                    {steps.map((step, index) => (
                        <div key={index} className="flex gap-6">
                            <div className="flex-shrink-0">
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                    <step.icon className="h-6 w-6" aria-hidden="true" />
                                </div>
                            </div>
                            <div>
                                <h3 className="text-xl font-semibold leading-7 text-slate-900">
                                    {step.title}
                                </h3>
                                <p className="mt-2 text-base leading-7 text-slate-600">
                                    {step.description}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </PublicLayout>
    );
}
