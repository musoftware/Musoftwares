import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Company() {
    return (
        <PublicLayout>
            <Head>
                <title>Company & History - Musoftware</title>
                <meta name="description" content="Learn about our structured software engineering approach and company history." />
            </Head>

            <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 bg-white">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="max-w-4xl mb-24">
                        <h1 className="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                            Software Engineering Studio
                        </h1>
                        <p className="text-xl text-slate-500 font-light">
                            We design, define, and execute software solutions with precision.
                        </p>
                    </div>

                    {/* History Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
                        
                        {/* Left Column: Timeline / Stats */}
                        <div className="lg:col-span-4 border-t lg:border-t-0 lg:border-r border-slate-100 pt-12 lg:pt-0 lg:pr-12">
                            <h2 className="text-sm font-bold text-slate-900 tracking-widest uppercase mb-12">Timeline</h2>
                            
                            <div className="space-y-12">
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">2013</h3>
                                    <p className="text-slate-500 font-light">Started as a software development initiative focused on practical engineering solutions.</p>
                                </div>
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">2014</h3>
                                    <p className="text-slate-500 font-light">Established as long-term development partners for growing businesses.</p>
                                </div>
                                <div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-2">Today</h3>
                                    <p className="text-slate-500 font-light">A structured engineering studio and the exclusive engineering partner for AMC Academy.</p>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Narrative */}
                        <div className="lg:col-span-8 pt-12 lg:pt-0">
                            <h2 className="text-3xl font-bold text-slate-900 mb-8">Our Background</h2>
                            
                            <div className="prose prose-slate prose-lg text-slate-500 font-light max-w-none">
                                <p className="mb-6">
                                    Our focus has always been on practical engineering solutions that solve real business problems. We don't just write code; we build the infrastructure that allows businesses to scale efficiently and securely.
                                </p>
                                
                                <p className="mb-6">
                                    Over the years, we have successfully operated across multiple layers of software development:
                                </p>
                                
                                <ul className="list-disc pl-5 mb-8 space-y-2">
                                    <li>Desktop applications and secure automation tools</li>
                                    <li>Web applications and internal dashboards</li>
                                    <li>Native mobile applications</li>
                                    <li>AI-powered assistants and chat systems</li>
                                    <li>Integrated enterprise resource management systems</li>
                                </ul>

                                <p className="mb-6">
                                    We have supported complex operations across various fields including e-commerce, healthcare, education, and finance. Throughout our journey, our primary goal has remained unchanged: to deliver reliable, scalable, and innovative software solutions.
                                </p>

                                <div className="p-8 mt-12 bg-slate-50 border border-slate-100 rounded-2xl">
                                    <blockquote className="text-2xl font-light text-slate-900 leading-relaxed mb-6">
                                        "Technology should not be a collection of disconnected tools. It should be a cohesive system that helps your business operate efficiently and securely at scale."
                                    </blockquote>
                                    <p className="text-sm font-bold text-slate-900 uppercase tracking-widest">Founder & CEO</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* Process Section */}
                    <div className="mt-32 pt-24 border-t border-slate-100">
                        <div className="max-w-3xl mb-16">
                            <h2 className="text-sm font-bold text-slate-900 tracking-widest uppercase mb-4">How We Work</h2>
                            <h3 className="text-4xl font-extrabold text-slate-900 tracking-tight">The Engineering Process</h3>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">0</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">Strategy</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    We define the scope, budget, and success criteria. A free consultation before any billing occurs.
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">1</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">Scope</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    You receive a written engineering brief containing the exact timeline, cost, and final deliverables.
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">2</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">Build</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    Execution begins. You receive weekly builds and live progress updates directly via WhatsApp.
                                </p>
                            </div>
                            
                            <div className="relative p-8 rounded-3xl bg-slate-50 border border-slate-100">
                                <span className="text-5xl font-extrabold text-slate-200 absolute top-6 right-6">3</span>
                                <h4 className="text-xl font-bold text-slate-900 mb-4 mt-8">Ship</h4>
                                <p className="text-slate-500 font-light leading-relaxed">
                                    We deploy the system, hand over the full source code, and provide 30 days of free support.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </PublicLayout>
    );
}
