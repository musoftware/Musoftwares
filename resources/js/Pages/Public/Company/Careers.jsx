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

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Engineering Opportunities"
                    title={
                        <>
                            Build Software That <br className="hidden sm:inline" />
                            <span className="text-[#748660]">Runs Global Businesses.</span>
                        </>
                    }
                    subtitle={__('landing_company.careers_subtitle') || 'Join an elite engineering studio dedicated to craftsmanship, clean architecture, and ultra-high reliability.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20">
                    <a href="mailto:careers@musoftwares.com?subject=Engineering Career Inquiry">
                        <button className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all">
                            SEND CV / GITHUB ➔
                        </button>
                    </a>
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'm interested in joining the engineering team.")}
                        className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                    >
                        WHATSAPP LEAD ARCHITECT
                    </button>
                </div>

                {/* Open Positions List */}
                <section className="px-6 max-w-[1400px] mx-auto">
                    <div className="mb-10 text-center">
                        <span className="text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal text-[#748660] font-bold">
                            Active Engineering Openings
                        </span>
                        <h2 className="text-2xl sm:text-3xl font-bold text-white font-sans mt-2">
                            Select Your Craft
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {jobs.map((job, idx) => (
                            <div
                                key={idx}
                                className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors"
                            >
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="w-10 h-10 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                            <Briefcase className="w-5 h-5" />
                                        </div>
                                        <span className="text-[11px] font-mono text-zinc-400 bg-black border border-[#2B2B2B] px-3 py-1">
                                            {job.type}
                                        </span>
                                    </div>
                                    <h3 className="text-lg font-bold text-white tracking-tight font-sans">
                                        {job.title}
                                    </h3>
                                    <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                                        {job.desc}
                                    </p>
                                    <div className="flex flex-wrap gap-1.5 pt-2">
                                        {job.techs.map((tech, tIdx) => (
                                            <span key={tIdx} className="text-[11px] font-mono bg-black border border-[#2B2B2B] text-zinc-300 px-2 py-0.5">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <a
                                    href={`mailto:careers@musoftwares.com?subject=${encodeURIComponent(job.subject)}`}
                                    className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
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
