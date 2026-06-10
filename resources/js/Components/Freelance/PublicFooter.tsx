import React from 'react';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function PublicFooter() {
    return (
        <footer className="bg-slate-50 border-t border-slate-200" aria-labelledby="footer-heading">
            <h2 id="footer-heading" className="sr-only">Footer</h2>
            <div className="mx-auto max-w-7xl px-6 pb-8 pt-16 sm:pt-24 lg:px-8 lg:pt-32">
                <div className="xl:grid xl:grid-cols-3 xl:gap-8">
                    <div className="space-y-8">
                        <Link href="/" className="flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-auto text-emerald-600" />
                            <span className="font-bold text-xl tracking-tight text-slate-900">ArabiJobs</span>
                        </Link>
                        <p className="text-sm leading-6 text-slate-600 max-w-xs">
                            {__('freelance.footer_desc', undefined, 'Connecting top talent with great businesses. The premier platform for freelance work in the Arab world.')}
                        </p>
                    </div>
                    <div className="mt-16 grid grid-cols-2 gap-8 xl:col-span-2 xl:mt-0">
                        <div className="md:grid md:grid-cols-2 md:gap-8">
                            <div>
                                <h3 className="text-sm font-semibold leading-6 text-slate-900">{__('freelance.footer_freelancers', undefined, 'For Freelancers')}</h3>
                                <ul role="list" className="mt-6 space-y-4">
                                    <li>
                                        <Link href={route('freelance.jobs.browse')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.nav.browse_jobs', undefined, 'Browse Jobs')}
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('register')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.create_profile', undefined, 'Create a Profile')}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                            <div className="mt-10 md:mt-0">
                                <h3 className="text-sm font-semibold leading-6 text-slate-900">{__('freelance.footer_clients', undefined, 'For Clients')}</h3>
                                <ul role="list" className="mt-6 space-y-4">
                                    <li>
                                        <Link href={route('freelance.freelancers.browse')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.nav.browse_freelancers', undefined, 'Find Talent')}
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('register')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.post_job', undefined, 'Post a Job')}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div className="md:grid md:grid-cols-2 md:gap-8">
                            <div>
                                <h3 className="text-sm font-semibold leading-6 text-slate-900">{__('freelance.footer_company', undefined, 'Company')}</h3>
                                <ul role="list" className="mt-6 space-y-4">
                                    <li>
                                        <Link href={route('freelance.about-us')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.nav.about_us', undefined, 'About Us')}
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('freelance.how-it-works')} className="text-sm leading-6 text-slate-600 hover:text-emerald-600">
                                            {__('freelance.nav.how_it_works', undefined, 'How it Works')}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="mt-16 border-t border-slate-900/10 pt-8 sm:mt-20 lg:mt-24">
                    <p className="text-xs leading-5 text-slate-500">&copy; {new Date().getFullYear()} ArabiJobs. {__('general.all_rights_reserved', undefined, 'All rights reserved.')}</p>
                </div>
            </div>
        </footer>
    );
}
