import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import {
    Code2,
    Database,
    Cpu,
    Server,
    ShieldCheck,
    Terminal,
    ArrowRight,
    MessageSquare,
    CheckCircle2,
    Layers,
    Workflow,
    Zap,
    Lock
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function CustomSolutions() {
    const phoneNumber = "201015218548";

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

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
            title: "Databases & High-Speed Cache",
            techs: ["PostgreSQL", "MySQL", "Redis", "SQLite", "BC-Math Precision"]
        },
        {
            title: "DevOps & Cloud Infrastructure",
            techs: ["Docker", "Linux VPS", "Nginx", "CI/CD Pipelines", "Automated Backups"]
        },
        {
            title: "Integrations & Automation",
            techs: ["WhatsApp Cloud API", "Payment Gateways", "SMS OTP Gateways", "AI Agents", "OpenAI"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>{__('home.custom_dev_title') || 'Custom Architecture & Software Engineering | Musoftware'}</title>
                <meta name="description" content="Bespoke software architecture, database engineering, and scalable enterprise systems built to your exact specifications." />
            </Head>

            <div className="w-full bg-[#fcfcfc] text-zinc-900 font-sans selection:bg-zinc-950 selection:text-white py-16 sm:py-24">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Hero */}
                    <div className="max-w-3xl mx-auto text-center mb-16 sm:mb-20">
                        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-100 border border-zinc-200 text-zinc-700 text-xs font-semibold mb-4">
                            <Code2 className="w-3.5 h-3.5 text-zinc-900" />
                            <span>{__('home.custom_dev_badge') || 'Custom Engineering'}</span>
                        </div>
                        <h1 className="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-zinc-950 mb-6 leading-tight">
                            {__('home.custom_dev_title') || 'Bespoke Software Architecture & Custom Systems'}
                        </h1>
                        <p className="text-zinc-600 text-sm sm:text-base md:text-lg leading-relaxed">
                            {__('home.custom_dev_desc') || 'We design and build bespoke database engines, multi-tenant platforms, and mission-critical systems engineered to your exact operational specifications.'}
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                            <Button
                                onClick={() => openWhatsApp("Hello Engineer Mahmoud, I would like to discuss building a custom system.")}
                                className="bg-zinc-950 text-white hover:bg-zinc-800 rounded-xl px-7 h-12 text-xs font-bold uppercase tracking-wider cursor-pointer flex items-center gap-2"
                            >
                                <MessageSquare className="w-4 h-4 text-emerald-400" />
                                <span>{__('home.talk_to_architect') || 'Talk Directly with the Architect'}</span>
                            </Button>
                            <Link href="/estimator">
                                <Button variant="outline" className="rounded-xl px-7 h-12 text-xs font-bold uppercase tracking-wider border-zinc-300">
                                    <span>{__('home.estimator_badge') || 'Budget Estimator'}</span>
                                    <ArrowRight className="w-3.5 h-3.5 ms-1.5 rtl:rotate-180" />
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Core Capabilities Grid */}
                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-20">
                        <div className="p-8 rounded-3xl bg-white border border-zinc-200 shadow-sm space-y-4">
                            <div className="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center">
                                <Database className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-zinc-900">Custom Database Engines</h3>
                            <p className="text-xs sm:text-sm text-zinc-600 leading-relaxed">
                                Multi-tenant database schemas with row-level security, optimistic & pessimistic locking, and BC-math precision for financial transactions.
                            </p>
                        </div>

                        <div className="p-8 rounded-3xl bg-white border border-zinc-200 shadow-sm space-y-4">
                            <div className="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center">
                                <Workflow className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-zinc-900">High-Delivery Webhook Pipelines</h3>
                            <p className="text-xs sm:text-sm text-zinc-600 leading-relaxed">
                                Asynchronous queue workers, instant payment verification, and real-time WhatsApp & SMS notifications with zero message loss.
                            </p>
                        </div>

                        <div className="p-8 rounded-3xl bg-white border border-zinc-200 shadow-sm space-y-4">
                            <div className="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center">
                                <Lock className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-zinc-900">Security & Role Architecture</h3>
                            <p className="text-xs sm:text-sm text-zinc-600 leading-relaxed">
                                Granular permissions, audit logging, rate limiting, and Sanctum token authorization preventing unauthorized access.
                            </p>
                        </div>
                    </div>

                    {/* Engineering Tech Stack */}
                    <div className="p-8 sm:p-12 rounded-3xl bg-zinc-950 text-white border border-zinc-800 shadow-xl mb-20">
                        <div className="flex items-center gap-3 mb-8">
                            <div className="w-10 h-10 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-emerald-400">
                                <Terminal className="w-5 h-5" />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold text-white">Engineering Tech Stack</h3>
                                <p className="text-xs text-zinc-400">Production-grade technologies selected for speed, security, and long-term scalability.</p>
                            </div>
                        </div>

                        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            {techCategories.map((category, idx) => (
                                <div key={idx} className="space-y-3">
                                    <h4 className="text-zinc-400 font-bold text-xs uppercase tracking-wider border-b border-zinc-800 pb-2">
                                        {category.title}
                                    </h4>
                                    <div className="flex flex-wrap gap-2">
                                        {category.techs.map((tech, tIdx) => (
                                            <span key={tIdx} className="bg-zinc-900 border border-zinc-800 px-3 py-1 rounded-lg text-xs font-semibold text-zinc-200">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Process Steps */}
                    <div className="text-center mb-12">
                        <h2 className="text-2xl sm:text-3xl font-extrabold text-zinc-900 mb-3">{__('home.process_title') || 'How We Work'}</h2>
                        <p className="text-xs sm:text-sm text-zinc-600 max-w-xl mx-auto">{__('home.process_subtitle') || 'A transparent 4-step engineering roadmap.'}</p>
                    </div>

                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {[
                            { step: "01", title: __('home.step_1_title') || "Send Requirements", desc: __('home.step_1_desc') || "Reach out with an overview of your requirements." },
                            { step: "02", title: __('home.step_2_title') || "Technical Scoping", desc: __('home.step_2_desc') || "We define architecture, database schema, and timeline." },
                            { step: "03", title: __('home.step_3_title') || "Proposal & Blueprint", desc: __('home.step_3_desc') || "Clear agreement with fixed milestones and cost." },
                            { step: "04", title: __('home.step_4_title') || "Execution & Delivery", desc: __('home.step_4_desc') || "Live development updates and staging testing." }
                        ].map((item, idx) => (
                            <div key={idx} className="p-6 rounded-2xl bg-white border border-zinc-200 shadow-sm text-center flex flex-col items-center">
                                <span className="w-10 h-10 rounded-xl bg-zinc-950 text-white flex items-center justify-center font-mono font-bold text-xs mb-3">
                                    {item.step}
                                </span>
                                <h4 className="font-bold text-sm text-zinc-900 mb-1">{item.title}</h4>
                                <p className="text-xs text-zinc-500 leading-relaxed">{item.desc}</p>
                            </div>
                        ))}
                    </div>

                </div>
            </div>
        </PublicLayout>
    );
}
