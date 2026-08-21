import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import {
    Code2,
    Database,
    Cpu,
    Server,
    ShieldCheck,
    Workflow,
    ArrowUpRight
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat } from '@/lib/whatsapp';

export default function CustomSolutions() {
    const techCategories = [
        {
            title: "Frontend & Interfaces",
            techs: ["React 19", "TypeScript", "Tailwind CSS v4", "Inertia.js", "Shadcn UI", "Next.js"]
        },
        {
            title: "Backend Engines",
            techs: ["Laravel 11+", "PHP 8.3", "Node.js", "Express", "REST APIs", "Webhook Pipelines"]
        },
        {
            title: "Databases & Ledgers",
            techs: ["PostgreSQL", "MySQL", "Redis", "SQLite", "BC-Math Precision"]
        },
        {
            title: "DevOps & Cloud",
            techs: ["Docker", "Linux VPS", "Nginx", "CI/CD Pipelines", "Automated Backups"]
        },
        {
            title: "Integrations & APIs",
            techs: ["WhatsApp Cloud API", "Payment Gateways", "SMS OTP Gateways", "AI Agents", "OpenAI"]
        }
    ];

    const capabilities = [
        {
            icon: Cpu,
            title: "High-Concurrency Backend Architecture",
            desc: "Scalable transaction backends engineered with asynchronous queue pipelines and sub-100ms response times."
        },
        {
            icon: Database,
            title: "Financial Precision & Dual Currency",
            desc: "BC-math rounding, double-entry ledgers, and dynamic multi-currency conversion without float discrepancies."
        },
        {
            icon: ShieldCheck,
            title: "Multi-Tenant Data Isolation",
            desc: "Zero-data-leak schema isolation with row-level policies and strictly authenticated client environments."
        },
        {
            icon: Workflow,
            title: "Automated Third-Party Integration",
            desc: "Bi-directional webhook ingestion, Meta Graph API sync, and reliable background synchronization."
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>{__('home.custom_dev_title') || 'Custom Architecture & Software Engineering | Musoftwares'}</title>
                <meta name="description" content="Bespoke software architecture, database engineering, and scalable enterprise systems built to your exact specifications." />
            </Head>

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Bespoke Engineering"
                    title={
                        <>
                            Custom Software Architecture. <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">Tailored To Your Scale.</span>
                        </>
                    }
                    subtitle="We build high-performance systems from the database schema up. No disposable templates, no fragile shortcuts — pure engineering."
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 -mt-8">
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss a custom engineering solution.")}
                        className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                    >
                        INITIATE TECHNICAL SCOPE ➔
                    </button>
                    <Link
                        href="/estimator"
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm"
                    >
                        {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'}
                    </Link>
                </div>

                {/* Capabilities Bento */}
                <section className="px-6 max-w-[1400px] mx-auto mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {capabilities.map((cap, idx) => {
                            const Icon = cap.icon;
                            return (
                                <div
                                    key={idx}
                                    className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {cap.title}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                            {cap.desc}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Tech Stack Matrix */}
                <section className="px-6 max-w-[1400px] mx-auto border-t border-black/5 pt-16">
                    <div className="text-center mb-12">
                        <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                            Production Technology Stack
                        </span>
                        <h2 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] font-sans tracking-tight mt-2">
                            Engineered with Modern Standards
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {techCategories.map((cat, idx) => (
                            <div key={idx} className="bg-white border border-black/5 rounded-[20px] p-6 space-y-4 shadow-sm hover:shadow-md transition-all">
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-[#0071e3]">
                                    {cat.title}
                                </h4>
                                <div className="flex flex-wrap gap-2 text-xs">
                                    {cat.techs.map((tech, tIdx) => (
                                        <span key={tIdx} className="bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/80 px-3 py-1 rounded-full font-medium">
                                            {tech}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
