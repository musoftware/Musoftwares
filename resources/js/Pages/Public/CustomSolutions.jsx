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

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Bespoke Engineering"
                    title={
                        <>
                            Custom Software Architecture. <br className="hidden sm:inline" />
                            <span className="text-[#748660]">Tailored To Your Scale.</span>
                        </>
                    }
                    subtitle="We build high-performance systems from the database schema up. No disposable templates, no fragile shortcuts — pure engineering."
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 -mt-8">
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss a custom engineering solution.")}
                        className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                    >
                        INITIATE TECHNICAL SCOPE ➔
                    </button>
                    <Link
                        href="/estimator"
                        className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
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
                                    className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-xl font-bold text-white font-sans">
                                            {cap.title}
                                        </h3>
                                        <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                            {cap.desc}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Tech Stack Matrix */}
                <section className="px-6 max-w-[1400px] mx-auto border-t border-[#222222] pt-20">
                    <div className="text-center mb-12">
                        <span className="text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal text-[#748660] font-bold">
                            Production Technology Stack
                        </span>
                        <h2 className="text-2xl sm:text-4xl font-bold text-white font-sans mt-2">
                            Engineered with Modern Standards
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {techCategories.map((cat, idx) => (
                            <div key={idx} className="bg-[#161616] border border-[#262626] p-6 space-y-4">
                                <h4 className="text-xs font-mono font-bold uppercase tracking-widest rtl:tracking-normal text-[#748660]">
                                    {cat.title}
                                </h4>
                                <div className="flex flex-wrap gap-1.5 font-mono text-xs">
                                    {cat.techs.map((tech, tIdx) => (
                                        <span key={tIdx} className="bg-black border border-[#2B2B2B] text-zinc-300 px-2.5 py-1">
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
