import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Briefcase, Building2, Server, GraduationCap, Code2, ShieldCheck } from 'lucide-react';

export default function Solutions() {
    return (
        <PublicLayout>
            <Head>
                <title>Solutions & Portfolio - Musoftware</title>
                <meta name="description" content="A selection of systems, tools, and platforms delivered for real clients." />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white border-b border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20 max-w-3xl">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            Solutions For Industries
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            We build architecture and systems specifically tailored to the rigorous requirements of your sector.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {[
                            { title: "Enterprise Systems", icon: <Building2 className="w-6 h-6" />, text: "Custom web applications, ERPS, and internal business management dashboards." },
                            { title: "E-commerce & Retail", icon: <Briefcase className="w-6 h-6" />, text: "Inventory, POS systems, order management, and native mobile apps." },
                            { title: "Telecom & Networks", icon: <Server className="w-6 h-6" />, text: "Custom CRM and operations management for major telecom distributors." },
                            { title: "Education & Academies", icon: <GraduationCap className="w-6 h-6" />, text: "E-learning platforms, student portals, and enterprise task management." },
                            { title: "Automation & Bots", icon: <Code2 className="w-6 h-6" />, text: "WhatsApp AI agents, bulk messaging tools, and internal automation." },
                            { title: "Desktop Utilities", icon: <ShieldCheck className="w-6 h-6" />, text: "Offline-capable encrypted desktop tools and hardware integrations." }
                        ].map((ind, i) => (
                            <div key={i} className="p-8 rounded-2xl bg-slate-50 border border-slate-100 transition-all group">
                                <div className="text-slate-900 mb-6">{ind.icon}</div>
                                <h4 className="text-xl font-bold text-slate-900 mb-2">{ind.title}</h4>
                                <p className="text-slate-500 font-light leading-relaxed">{ind.text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-24 lg:py-32 bg-white">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 max-w-3xl">
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Built, Shipped, Running</h2>
                        <p className="text-xl text-slate-500 font-light">
                            A selection of systems, tools, and platforms delivered for real clients.
                        </p>
                    </div>

                    <div className="space-y-12">
                        {/* Case Study 1 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">Telecom & Distribution</span>
                                <h3 className="text-2xl font-bold text-slate-900">Vodafone CRM</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">The System</h4>
                                    <p className="text-sm text-slate-500 font-light">Custom CRM and operations management system for a major Vodafone Egypt distributor.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Key Features</h4>
                                    <p className="text-sm text-slate-500 font-light">B2B recharge processing, ISP management platform, and automated billing.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Outcome</h4>
                                    <p className="text-sm text-slate-500 font-light">Centralized agent management and eliminated manual reconciliation errors.</p>
                                </div>
                            </div>
                        </div>

                        {/* Case Study 2 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">Retail & Inventory</span>
                                <h3 className="text-2xl font-bold text-slate-900">Stock Manager</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">The System</h4>
                                    <p className="text-sm text-slate-500 font-light">Inventory and POS system with multi-location support and KPI reporting.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Key Features</h4>
                                    <p className="text-sm text-slate-500 font-light">Role-based access controls, real-time stock tracking, and automated alerts.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Outcome</h4>
                                    <p className="text-sm text-slate-500 font-light">Unified stock visibility across all physical branches and warehouses.</p>
                                </div>
                            </div>
                        </div>

                        {/* Case Study 3 */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 p-10 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-slate-200 pb-8 lg:pb-0 lg:pr-8">
                                <span className="text-xs font-bold uppercase tracking-widest text-slate-900 mb-2 block">Education & Operations</span>
                                <h3 className="text-2xl font-bold text-slate-900">AMC Tasks</h3>
                            </div>
                            <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">The System</h4>
                                    <p className="text-sm text-slate-500 font-light">Enterprise task management platform built on Laravel & Livewire.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Key Features</h4>
                                    <p className="text-sm text-slate-500 font-light">Custom ecosystem with desktop tools, file synchronization, and social feeds.</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">Outcome</h4>
                                    <p className="text-sm text-slate-500 font-light">Successfully managed operations for thousands of affiliates worldwide.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
