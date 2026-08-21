import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Briefcase, ArrowUpRight, CheckCircle2 } from 'lucide-react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat } from '@/lib/whatsapp';

export default function Careers({ canLogin, canRegister }) {
    const jobs = [
        {
            title: __('landing_company.careers_job_1_title') || 'Senior Backend Engineer (Laravel / PHP 8.3)',
            type: __('landing_company.careers_job_1_type') || 'Full-time / Remote',
            desc: __('landing_company.careers_job_1_desc') || 'Architect and scale high-concurrency SaaS backends, queue pipelines, and multi-tenant financial ledgers.',
            techs: ['Laravel', 'PostgreSQL', 'Redis', 'Docker'],
            subject: 'Senior Backend Engineer Application',
        },
        {
            title: __('landing_company.careers_job_2_title') || 'Frontend Architect (React 19 / TypeScript)',
            type: __('landing_company.careers_job_2_type') || 'Full-time / Remote',
            desc: __('landing_company.careers_job_2_desc') || 'Engineer delight-driven, high-performance web user interfaces with Apple-grade aesthetic precision.',
            techs: ['React', 'TypeScript', 'Tailwind', 'GSAP'],
            subject: 'Frontend Architect Application',
        },
    ];

    return (
        <PublicLayout>
            <Head title={`${__('landing_company.careers_meta_title') || 'Careers'} | Musoftwares`} />

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Engineering Opportunities"
                    title={
                        <>
                            Build Software That <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">Runs Global Businesses.</span>
                        </>
                    }
                    subtitle={__('landing_company.careers_subtitle') || 'Join an elite engineering studio dedicated to craftsmanship, clean architecture, and ultra-high reliability.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20">
                    <a href="mailto:careers@musoftwares.com?subject=Engineering Career Inquiry">
                        <button className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                            SEND CV / GITHUB ➔
                        </button>
                    </a>
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'm interested in joining the engineering team.")}
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer"
                    >
                        WHATSAPP LEAD ARCHITECT
                    </button>
                </div>

                {/* Open Positions List */}
                <section className="px-6 max-w-[1400px] mx-auto">
                    <div className="mb-10 text-center">
                        <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                            Active Engineering Openings
                        </span>
                        <h2 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight mt-2">
                            Select Your Craft
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {jobs.map((job, idx) => (
                            <div
                                key={idx}
                                className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm"
                            >
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                            <Briefcase className="w-5 h-5" />
                                        </div>
                                        <span className="text-xs text-[#1d1d1f]/70 bg-[#f5f5f7] border border-black/5 px-3 py-1 rounded-full font-medium">
                                            {job.type}
                                        </span>
                                    </div>
                                    <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                        {job.title}
                                    </h3>
                                    <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                                        {job.desc}
                                    </p>
                                    <div className="flex flex-wrap gap-2 pt-2">
                                        {job.techs.map((tech, tIdx) => (
                                            <span key={tIdx} className="text-xs bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/80 px-2.5 py-1 rounded-full font-medium">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <a
                                    href={`mailto:careers@musoftwares.com?subject=${encodeURIComponent(job.subject)}`}
                                    className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse"
                                >
                                    <span>APPLY FOR THIS ROLE</span>
                                    <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                                </a>
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
