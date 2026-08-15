import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Sparkles, Code, Terminal, Database, ArrowRight, MessageSquare, Briefcase } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Careers({ canLogin, canRegister }) {
    const phoneNumber = "201015218548";

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

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
        <PublicLayout auth={{ user: null }}>
            <Head title={`${__('landing_company.careers_meta_title') || 'Careers'} | Musoftware`} />

            <div className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* Hero Section */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-20 sm:mb-28">
                    <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                        Engineering Opportunities
                    </p>

                    <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                        {__('landing_company.careers_title') || 'Build software that'} <br className="hidden sm:inline" />
                        <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                            runs businesses.
                        </span>
                    </h1>

                    <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-10 sm:mb-12 font-medium leading-snug tracking-tight">
                        {__('landing_company.careers_subtitle') || 'Join an elite engineering studio dedicated to craftsmanship, clean architecture, and ultra-high reliability.'}
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 w-full sm:w-auto items-center justify-center">
                        <a href="mailto:admin@musoftwares.com?subject=Engineering Career Inquiry" className="w-full sm:w-auto">
                            <button className="bg-[#1d1d1f] hover:bg-[#333336] text-white px-8 py-3.5 rounded-full text-[17px] font-semibold w-full sm:w-auto transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-95">
                                {__('landing_company.careers_send_resume') || 'Send Your Resume'}
                            </button>
                        </a>
                        <button
                            onClick={() => openWhatsApp("Hello Mahmoud, I'm interested in career opportunities at Musoftware.")}
                            className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer"
                        >
                            <span>Quick Chat on WhatsApp</span>
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                        </button>
                    </div>
                </section>

                {/* Job Openings Grid */}
                <section className="px-6 max-w-6xl mx-auto">
                    <div className="text-center mb-12 sm:mb-16">
                        <h2 className="text-2xl sm:text-4xl font-bold text-[#1d1d1f] tracking-tight mb-3">
                            Open Positions
                        </h2>
                        <p className="text-[#86868b] text-sm sm:text-base">
                            We value autonomy, deep domain knowledge, and meticulous attention to detail.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {jobs.map((job, idx) => (
                            <div key={idx} className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full border border-[#d2d2d7]/50 shadow-xs">
                                <div>
                                    <div className="flex items-center justify-between mb-4">
                                        <span className="text-[11px] font-bold uppercase tracking-wider text-[#0066cc] bg-blue-50 px-3 py-1 rounded-full border border-blue-200/60">
                                            {job.type}
                                        </span>
                                    </div>
                                    <h3 className="text-xl sm:text-2xl font-bold text-[#1d1d1f] tracking-tight mb-3">
                                        {job.title}
                                    </h3>
                                    <p className="text-[15px] text-[#86868b] leading-relaxed font-medium mb-6">
                                        {job.desc}
                                    </p>
                                    <div className="flex flex-wrap gap-2 mb-8">
                                        {job.techs.map((t, i) => (
                                            <span key={i} className="px-2.5 py-1 rounded-lg bg-white border border-[#d2d2d7]/60 text-xs font-semibold text-zinc-700">
                                                {t}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <a href={`mailto:admin@musoftwares.com?subject=${encodeURIComponent(job.subject)}`}>
                                    <Button className="w-full h-12 rounded-full bg-[#1d1d1f] hover:bg-[#333336] text-white font-bold text-xs uppercase tracking-wider cursor-pointer">
                                        <span>Apply for this Role</span>
                                        <ArrowRight className="w-3.5 h-3.5 ms-2 rtl:rotate-180" />
                                    </Button>
                                </a>
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
