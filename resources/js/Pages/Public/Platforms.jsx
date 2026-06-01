import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowRight } from 'lucide-react';

export default function Platforms() {
    return (
        <PublicLayout>
            <Head>
                <title>Platforms & Systems - Musoftware</title>
                <meta name="description" content="Custom web apps, ERPs, and business automation solutions." />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white border-b border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20 max-w-3xl">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            What We Build
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            From a single focused tool to a full business platform, we build the software that runs your business operations.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <LayoutDashboard className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">Dashboards & ERPs</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                Replace manual spreadsheets with live dashboards, custom reporting, inventory management, and role-based access controls.
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Custom reporting & analytics</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Inventory & order management</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Multi-user roles & permissions</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                View Pricing <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                        
                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Workflow className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">Automation & Integrations</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                Connect your tools and eliminate repetitive manual work with smart workflows, custom bots, and scheduled tasks.
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>API & third-party integrations</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Automated data sync & notifications</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Custom bots & scheduled tasks</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                View Pricing <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Globe className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">Custom Web Apps</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                Scalable web applications built to your exact process using modern architecture, completely responsive by default.
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Scalable modern architecture</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Mobile responsive by default</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Secure client portals & forms</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                View Pricing <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-colors group hover:bg-slate-100">
                            <Monitor className="w-10 h-10 text-slate-900 mb-6" />
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">Desktop Apps & Secure Tools</h3>
                            <p className="text-lg text-slate-500 font-light mb-6">
                                Offline-capable, encrypted desktop tools for sensitive internal operations across multiple operating systems.
                            </p>
                            <ul className="space-y-3 mb-8">
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Offline capable applications</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>End-to-end encryption</li>
                                <li className="flex items-center text-slate-600 font-light"><div className="w-1.5 h-1.5 bg-slate-400 rounded-full mr-3"></div>Windows, macOS, Linux</li>
                            </ul>
                            <Link href="/pricing" className="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-slate-600 transition-colors">
                                View Pricing <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
