import React from 'react';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { SeoHead } from '@/Components/ui/SeoHead';
import PublicNavbar from '@/Components/Freelance/PublicNavbar';
import PublicFooter from '@/Components/Freelance/PublicFooter';

export default function Landing({ canLogin, canRegister }: { canLogin: boolean, canRegister: boolean }) {
    return (
        <div className="min-h-screen bg-white flex flex-col">
            <SeoHead 
                title={__('freelance.seo.landing_title', undefined, 'ArabiJobs Freelance')}
                description={__('freelance.seo.landing_desc', undefined, 'Find top talent or great jobs on ArabiJobs Freelance.')}
                jsonLd={{
                    "@context": "https://schema.org",
                    "@type": "WebSite",
                    "name": "ArabiJobs",
                    "url": typeof window !== 'undefined' ? window.location.origin : ''
                }}
            />
            
            <PublicNavbar canLogin={canLogin} canRegister={canRegister} />

            <div className="relative isolate px-6 pt-14 lg:px-8 flex-grow">
                <div className="mx-auto max-w-7xl py-32 sm:py-48 lg:py-56">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                            {__('freelance.landing_heading', undefined, 'The Premier Freelance Marketplace')}
                        </h1>
                        <p className="mt-6 text-lg leading-8 text-gray-600">
                            {__('freelance.landing_subheading', undefined, 'Find top talent or great jobs. ArabiJobs provides a dedicated platform to connect freelancers with businesses instantly.')}
                        </p>
                        <div className="mt-10 flex items-center justify-center gap-x-6">
                            <Link href={route('register')}>
                                <Button size="lg" className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-8">
                                    {__('freelance.get_started', undefined, 'Get Started')}
                                </Button>
                            </Link>
                            <Link href={route('login')} className="text-sm font-semibold leading-6 text-gray-900 hover:text-emerald-600">
                                {__('general.learn_more', undefined, 'Learn more')} <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <PublicFooter />
        </div>
    );
}
