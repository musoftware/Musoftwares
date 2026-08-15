import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ChevronRight,
    Check,
    Code,
    Database,
    Smartphone,
    LayoutTemplate,
    Terminal,
    Cpu,
    ArrowRight,
    MessageSquare
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Home() {
    const phoneNumber = "201015218548";

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    return (
        <PublicLayout>
            <Head title="Musoftware - We build the software that runs your business">
                <meta name="description" content="From a 5-hour bug fix to a full SaaS platform. One studio, 10+ years, 30+ products shipped." />
            </Head>

            <div className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* 1. HERO SECTION */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-24 sm:mb-40 relative">
                    <div className="z-10 flex flex-col items-center w-full">
                        <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                            Now accepting new projects.
                        </p>
                        
                        <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                            We build the software that <br className="hidden sm:inline" />
                            <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                                runs your business.
                            </span>
                        </h1>

                        <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-10 sm:mb-12 font-medium leading-snug tracking-tight">
                            From a 5-hour bug fix to a full SaaS platform. One studio, 10+ years, 30+ products shipped.
                        </p>

                        <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 mb-16 sm:mb-24 w-full sm:w-auto items-center justify-center">
                            <button
                                onClick={() => openWhatsApp("Hello Mahmoud, I want to start a project with Musoftware.")}
                                className="bg-[#1d1d1f] hover:bg-[#333336] text-white px-8 py-3.5 rounded-full text-[17px] font-semibold w-full sm:w-auto transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-95"
                            >
                                Start Your Project
                            </button>
                            <Link
                                href="/portfolio"
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer"
                            >
                                <span>Explore Our Work</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                            </Link>
                        </div>

                        {/* Engineering Stack */}
                        <div className="w-full pt-12 sm:pt-16 border-t border-[#d2d2d7]/50">
                            <p className="text-xs font-semibold text-[#86868b] mb-6 sm:mb-8 uppercase tracking-widest">Engineering Stack</p>
                            <div className="flex flex-wrap justify-center items-center gap-6 sm:gap-10 text-[#86868b]">
                                <div className="flex items-center gap-2"><Code className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">.NET</span></div>
                                <div className="flex items-center gap-2"><Database className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">Laravel</span></div>
                                <div className="flex items-center gap-2"><Smartphone className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">Flutter</span></div>
                                <div className="flex items-center gap-2"><LayoutTemplate className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">React</span></div>
                                <div className="flex items-center gap-2"><Terminal className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">Python</span></div>
                                <div className="flex items-center gap-2"><Cpu className="w-5 h-5" strokeWidth={1.5} /><span className="text-[15px] font-medium">AI</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 2. HOW WE WORK / PROCESS */}
                <section className="px-6 max-w-7xl mx-auto mb-24 sm:mb-40">
                    <h2 className="text-3xl sm:text-5xl md:text-6xl font-bold text-[#1d1d1f] mb-12 sm:mb-20 text-center tracking-tight leading-tight">
                        Engineering Process.
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6">
                        {/* Step 1 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 flex flex-col items-center text-center">
                            <div className="w-12 h-12 mb-6 text-[15px] font-semibold text-[#86868b] flex items-center justify-center">01.</div>
                            <h3 className="text-[21px] font-semibold text-[#1d1d1f] mb-2 tracking-tight">Idea</h3>
                            <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">Defining requirements &amp; scope.</p>
                        </div>
                        {/* Step 2 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 flex flex-col items-center text-center">
                            <div className="w-12 h-12 mb-6 text-[15px] font-semibold text-[#86868b] flex items-center justify-center">02.</div>
                            <h3 className="text-[21px] font-semibold text-[#1d1d1f] mb-2 tracking-tight">Check Existing</h3>
                            <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">Evaluating market solutions.</p>
                        </div>
                        {/* Step 3 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 flex flex-col items-center text-center border-2 border-[#0066cc]/30 shadow-xs">
                            <div className="w-12 h-12 mb-6 text-[15px] font-semibold text-[#0066cc] flex items-center justify-center">03.</div>
                            <h3 className="text-[21px] font-semibold text-[#1d1d1f] mb-2 tracking-tight">Build</h3>
                            <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">Agile engineering sprints.</p>
                        </div>
                        {/* Step 4 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 flex flex-col items-center text-center">
                            <div className="w-12 h-12 mb-6 text-[15px] font-semibold text-[#86868b] flex items-center justify-center">04.</div>
                            <h3 className="text-[21px] font-semibold text-[#1d1d1f] mb-2 tracking-tight">Deploy</h3>
                            <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">Staging to production release.</p>
                        </div>
                        {/* Step 5 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 flex flex-col items-center text-center">
                            <div className="w-12 h-12 mb-6 text-[15px] font-semibold text-[#86868b] flex items-center justify-center">05.</div>
                            <h3 className="text-[21px] font-semibold text-[#1d1d1f] mb-2 tracking-tight">Support</h3>
                            <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">Continuous maintenance.</p>
                        </div>
                    </div>
                </section>

                {/* 3. INVESTMENT MODELS / PRICING */}
                <section className="px-6 max-w-7xl mx-auto mb-24 sm:mb-40">
                    <div className="text-center mb-16 sm:mb-20">
                        <h2 className="text-3xl sm:text-5xl md:text-6xl font-bold text-[#1d1d1f] mb-4 tracking-tight leading-tight">
                            Investment Models.
                        </h2>
                        <p className="text-base sm:text-xl text-[#86868b] max-w-2xl mx-auto font-medium tracking-tight">
                            Transparent pricing blocks tailored to your immediate technical requirements.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* Card 1 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-[13px] font-semibold text-[#86868b] mb-6 uppercase tracking-wider">Fix &amp; Improve</div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-[44px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">$45</span>
                                    <span className="text-[17px] text-[#86868b] font-medium">/ 5h block</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 sm:mb-10 leading-relaxed font-medium">
                                    Ideal for urgent bug fixes or minor system updates.
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Codebase debugging</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Performance optimization</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Minor UI/UX updates</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsApp("Hello Mahmoud, I want to book a 5h Fix & Improve block.")}
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Book Block</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </button>
                        </div>

                        {/* Card 2: Most Popular */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full relative border border-[#0066cc]/20">
                            <div className="absolute top-8 end-8 text-[11px] sm:text-[12px] font-bold text-[#0066cc] uppercase tracking-wider bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200/60">
                                Most Popular
                            </div>
                            <div>
                                <div className="text-[13px] font-semibold text-[#1d1d1f] mb-6 uppercase tracking-wider">Build New</div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-[44px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">$110</span>
                                    <span className="text-[17px] text-[#86868b] font-medium">/ 12h block</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 sm:mb-10 leading-relaxed font-medium">
                                    Perfect for developing specific new features or integrating APIs.
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">New module development</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Third-party API integration</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Database architecture updates</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Priority engineering queue</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsApp("Hello Mahmoud, I want to book a 12h Build New sprint.")}
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Book Sprint</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </button>
                        </div>

                        {/* Card 3 */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-[13px] font-semibold text-[#86868b] mb-6 uppercase tracking-wider">Full Project</div>
                                <div className="mb-4 h-[56px] flex items-end">
                                    <span className="text-[28px] sm:text-[32px] font-bold text-[#1d1d1f] tracking-tight leading-none pb-1">Custom Scope</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 sm:mb-10 leading-relaxed font-medium">
                                    End-to-end development for MVPs or complete platforms.
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Dedicated engineering team</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Full architecture &amp; design</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Long-term SLA options</span>
                                    </li>
                                </ul>
                            </div>
                            <Link
                                href="/estimator"
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Get Custom Quote</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </Link>
                        </div>
                    </div>
                </section>

                {/* 4. SOCIAL PROOF / TESTIMONIALS */}
                <section className="px-6 max-w-6xl mx-auto pt-16 sm:pt-24 pb-16 sm:pb-24 border-t border-[#d2d2d7]/50">
                    <h2 className="text-2xl sm:text-4xl font-bold text-[#1d1d1f] mb-12 sm:mb-20 text-center tracking-tight leading-tight">
                        Trusted by technical teams across MENA.
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        {/* Testimonial 1 */}
                        <div className="bg-[#f5f5f7] p-8 rounded-[28px] flex flex-col justify-between">
                            <p className="text-[16px] sm:text-[17px] text-[#1d1d1f] leading-relaxed mb-8 font-medium tracking-tight">
                                "They handled our legacy migration with exceptional precision. The custom .NET backend they delivered cut our processing latency in half."
                            </p>
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full overflow-hidden bg-white border border-zinc-200 flex items-center justify-center font-bold text-zinc-700">
                                    AT
                                </div>
                                <div>
                                    <div className="text-[15px] font-bold text-[#1d1d1f]">Ahmed T.</div>
                                    <div className="text-[13px] text-[#86868b] font-medium">CTO, FinTech Startup</div>
                                </div>
                            </div>
                        </div>

                        {/* Testimonial 2 */}
                        <div className="bg-[#f5f5f7] p-8 rounded-[28px] flex flex-col justify-between">
                            <p className="text-[16px] sm:text-[17px] text-[#1d1d1f] leading-relaxed mb-8 font-medium tracking-tight">
                                "We use their 12h blocks for all our non-core integrations. Fast, reliable code every single time. It's like having an elite engineering team on standby."
                            </p>
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full overflow-hidden bg-white border border-zinc-200 flex items-center justify-center font-bold text-zinc-700">
                                    SM
                                </div>
                                <div>
                                    <div className="text-[15px] font-bold text-[#1d1d1f]">Sara M.</div>
                                    <div className="text-[13px] text-[#86868b] font-medium">VP Product, SaaS Platform</div>
                                </div>
                            </div>
                        </div>

                        {/* Testimonial 3 */}
                        <div className="bg-[#f5f5f7] p-8 rounded-[28px] flex flex-col justify-between">
                            <p className="text-[16px] sm:text-[17px] text-[#1d1d1f] leading-relaxed mb-8 font-medium tracking-tight">
                                "Their React and Python stack knowledge is top-tier. They built our internal tracking dashboard in a fraction of the time we estimated internally."
                            </p>
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full overflow-hidden bg-white border border-zinc-200 flex items-center justify-center font-bold text-zinc-700">
                                    KE
                                </div>
                                <div>
                                    <div className="text-[15px] font-bold text-[#1d1d1f]">Karim E.</div>
                                    <div className="text-[13px] text-[#86868b] font-medium">Head of IT, Logistics</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
