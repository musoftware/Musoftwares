import ApplicationLogo from '@/Components/ApplicationLogo';
import AuthIllustration from '@/Components/AuthIllustration';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, useRef } from 'react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

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
        <div className="min-h-screen flex w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white">
            {/* Desktop Left Column: Exact Studio View */}
            <div className="hidden lg:flex lg:w-[42%] xl:w-[45%] bg-[#0D0D0D] text-zinc-300 flex-col justify-between p-12 lg:p-16 border-e border-[#222222]">
                {/* Top Branding Header */}
                <div className="flex items-center space-x-3">
                    <Link href="/" className="flex items-center space-x-3 group">
                        <span className="text-2xl font-black tracking-tighter text-white font-sans">
                            MUSOFT
                        </span>
                        <span className="h-4 w-[1px] bg-zinc-700"></span>
                        <span className="text-[11px] font-mono tracking-widest uppercase text-zinc-400">
                            STUDIO
                        </span>
                    </Link>
                </div>

                {/* Middle Content: Subtle Illustration & Copy */}
                <div className="my-auto max-w-sm space-y-8 py-8">
                    <div className="p-5 bg-[#161616] border border-[#262626]">
                        <AuthIllustration />
                    </div>
                    <div className="space-y-2 font-mono">
                        <p className="text-sm font-bold tracking-wider text-white uppercase">{__('general.enterprise_operations_simplified') || 'Enterprise Engineering Console'}</p>
                        <p className="text-xs text-zinc-400 leading-relaxed font-normal">{__('general.streamlined_workspace_management_automated_client_interactions_and_secure_invoicing_in_one_quiet_utility_first_environment') || 'Bespoke ERP orchestration, high-speed Meta API pipelines, and dedicated workspace management in one unified environment.'}</p>
                    </div>
                </div>

                {/* Bottom Footer Links */}
                <div className="flex items-center justify-between text-xs font-mono text-zinc-400 border-t border-[#222222] pt-6">
                    <span>© Musoftwares Studio</span>
                    <div className="flex space-x-6 text-zinc-400">
                        <Link href="/docs" className="hover:text-white transition-colors">{__('general.help') || 'Docs'}</Link>
                        <Link href="/privacy-policy" className="hover:text-white transition-colors">{__('general.privacy') || 'Privacy'}</Link>
                        <Link href="/terms-of-service" className="hover:text-white transition-colors">{__('general.terms') || 'Terms'}</Link>
                    </div>
                </div>
            </div>

            {/* Right Side / Mobile Centered Container */}
            <div className="flex-1 flex flex-col justify-center px-4 py-12 sm:px-6 lg:px-16 relative bg-[#111111]">
                {/* Mobile / Tablet Minimal Top Bar */}
                <div className="absolute top-6 start-6 lg:hidden flex items-center space-x-2.5">
                    <Link href="/" className="flex items-center space-x-2 group">
                        <span className="text-lg font-black tracking-tighter text-white font-sans">
                            MUSOFT
                        </span>
                        <span className="text-[10px] font-mono tracking-widest uppercase text-zinc-400">
                            STUDIO
                        </span>
                    </Link>
                </div>

                {/* Centered Compact Auth Card Anchor */}
                <div ref={containerRef} className="w-full max-w-[420px] mx-auto bg-[#161616] border border-[#2B2B2B] p-8 sm:p-10 shadow-2xl transition-all">
                    {children}
                </div>
            </div>
            <FloatingWhatsAppButton />
        </div>
    );
}
