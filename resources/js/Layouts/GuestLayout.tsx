import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, useRef } from 'react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { ShieldCheck, Cpu, Database, Zap } from 'lucide-react';

export default function Guest({ children }: PropsWithChildren) {
    const containerRef = useRef<HTMLDivElement>(null);

    useGSAP(() => {
        if (containerRef.current) {
            gsap.from(containerRef.current, {
                y: 20,
                opacity: 0,
                duration: 0.6,
                ease: 'power3.out',
            });
        }
    });

    return (
        <div className="min-h-screen flex w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
            {/* Desktop Left Column: Apple Showcase View */}
            <div className="hidden lg:flex lg:w-[42%] xl:w-[45%] bg-[#f5f5f7] text-[#1d1d1f] flex-col justify-between p-12 lg:p-16 border-e border-black/5 relative overflow-hidden">
                {/* Subtle Ambient Glow */}
                <div className="absolute -top-32 -left-32 w-96 h-96 bg-[#0071e3]/5 rounded-full blur-3xl pointer-events-none" />

                {/* Top Branding Header */}
                <div className="flex items-center space-x-3 relative z-10">
                    <Link href="/" className="flex items-center space-x-3 group">
                        <div className="w-10 h-10 rounded-xl bg-white border border-black/5 shadow-xs flex items-center justify-center p-2">
                            <ApplicationLogo className="w-full h-full object-contain" />
                        </div>
                        <div className="flex flex-col">
                            <span className="text-xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                Musoftwares
                            </span>
                            <span className="text-[10px] uppercase tracking-wider text-[#1d1d1f]/50 font-semibold">
                                Studio &amp; Engineering Firm
                            </span>
                        </div>
                    </Link>
                </div>

                {/* Middle Content: Apple Bento Preview Card */}
                <div className="my-auto max-w-sm space-y-6 py-8 relative z-10">
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-xl shadow-black/5 space-y-5">
                        <div className="flex items-center justify-between">
                            <span className="text-[10px] uppercase tracking-wider px-3 py-1 bg-[#0071e3]/10 text-[#0071e3] rounded-full font-semibold">
                                Enterprise Studio
                            </span>
                            <span className="w-2 h-2 rounded-full bg-[#34c759] animate-pulse"></span>
                        </div>

                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight">
                                Unified Business Backbone
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans">
                                High-throughput ERP ledgers, Meta Graph API integrations, and cloud workspace orchestration in one secure console.
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-2 pt-2 border-t border-black/5 text-[11px] text-[#1d1d1f]/70">
                            <div className="flex items-center gap-1.5 p-2 rounded-xl bg-[#f5f5f7]">
                                <ShieldCheck className="w-4 h-4 text-[#0071e3] shrink-0" />
                                <span>Zero Tech Debt</span>
                            </div>
                            <div className="flex items-center gap-1.5 p-2 rounded-xl bg-[#f5f5f7]">
                                <Zap className="w-4 h-4 text-[#0071e3] shrink-0" />
                                <span>Real-Time Events</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Footer Links */}
                <div className="flex items-center justify-between text-xs text-[#1d1d1f]/50 border-t border-black/5 pt-6 relative z-10">
                    <span>© {new Date().getFullYear()} Musoftwares Studio</span>
                    <div className="flex space-x-6 text-[#1d1d1f]/60">
                        <Link href="/docs" className="hover:text-[#0071e3] transition-colors">{__('general.help') || 'Docs'}</Link>
                        <Link href="/privacy-policy" className="hover:text-[#0071e3] transition-colors">{__('general.privacy') || 'Privacy'}</Link>
                        <Link href="/terms-of-service" className="hover:text-[#0071e3] transition-colors">{__('general.terms') || 'Terms'}</Link>
                    </div>
                </div>
            </div>

            {/* Right Side / Centered Form Container */}
            <div className="flex-1 flex flex-col justify-center px-4 py-12 sm:px-6 lg:px-16 relative bg-white">
                {/* Mobile / Tablet Minimal Top Bar */}
                <div className="absolute top-6 start-6 lg:hidden flex items-center space-x-2.5">
                    <Link href="/" className="flex items-center space-x-2 group">
                        <div className="w-8 h-8 rounded-lg bg-[#f5f5f7] border border-black/5 flex items-center justify-center p-1.5">
                            <ApplicationLogo className="w-full h-full object-contain" />
                        </div>
                        <span className="text-base font-bold tracking-tight text-[#1d1d1f] font-sans">
                            Musoftwares
                        </span>
                    </Link>
                </div>

                {/* Centered Compact Auth Card Anchor */}
                <div ref={containerRef} className="w-full max-w-[420px] mx-auto bg-white border border-black/5 rounded-[28px] p-8 sm:p-10 shadow-xl shadow-black/5 transition-all">
                    {children}
                </div>
            </div>
            <FloatingWhatsAppButton />
        </div>
    );
}
