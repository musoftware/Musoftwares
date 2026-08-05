import ApplicationLogo from '@/Components/ApplicationLogo';
import AuthIllustration from '@/Components/AuthIllustration';
import { Link, router } from '@inertiajs/react';
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
        <div className="min-h-screen flex w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans selection:bg-zinc-900 selection:text-white dark:selection:bg-white dark:selection:text-zinc-900">
            {/* Desktop Left Column: Calm Minimal Supportive View */}
            <div className="hidden lg:flex lg:w-[42%] xl:w-[45%] bg-zinc-100/50 dark:bg-zinc-900/30 text-zinc-800 dark:text-zinc-200 flex-col justify-between p-12 lg:p-16 border-e border-zinc-200/80 dark:border-zinc-800/80">
                {/* Top Branding Header */}
                <div className="flex items-center space-x-3">
                    <Link href="/" className="flex items-center space-x-2.5 group">
                        <div className="w-8 h-8 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 flex items-center justify-center shadow-xs transition-transform duration-200 group-hover:scale-105">
                            <ApplicationLogo className="w-4 h-4 fill-current" />
                        </div>
                        <span className="font-semibold text-base tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.musoftware')}</span>
                    </Link>
                </div>

                {/* Middle Content: Subtle Editorial Illustration & Calm Support Text */}
                <div className="my-auto max-w-sm space-y-8 py-8">
                    <AuthIllustration />
                    <div className="space-y-2">
                        <p className="text-sm font-medium tracking-tight text-zinc-900 dark:text-zinc-100">{__('general.enterprise_operations_simplified')}</p>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed font-normal">{__('general.streamlined_workspace_management_automated_client_interactions_and_secure_invoicing_in_one_quiet_utility_first_environment')}</p>
                    </div>
                </div>

                {/* Bottom Footer Links */}
                <div className="flex items-center justify-between text-xs text-zinc-400 dark:text-zinc-500 border-t border-zinc-200/60 dark:border-zinc-800/60 pt-6">
                    <span>© Musoftware Inc.</span>
                    <div className="flex space-x-6 text-zinc-500 dark:text-zinc-400">
                        <span className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer">{__('general.help') || 'Help'}</span>
                        <Link href="/privacy-policy" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer">{__('general.privacy') || 'Privacy'}</Link>
                        <Link href="/terms-of-service" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer">{__('general.terms') || 'Terms'}</Link>
                    </div>
                </div>
            </div>

            {/* Right Side / Mobile Centered Container */}
            <div className="flex-1 flex flex-col justify-center px-4 py-12 sm:px-6 lg:px-16 relative bg-white dark:bg-zinc-950">
                {/* Mobile / Tablet Minimal Top Bar */}
                <div className="absolute top-6 start-6 lg:hidden flex items-center space-x-2.5">
                    <Link href="/" className="flex items-center space-x-2 group">
                        <div className="w-8 h-8 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 flex items-center justify-center shadow-xs transition-transform group-hover:scale-105">
                            <ApplicationLogo className="w-4 h-4 fill-current" />
                        </div>
                        <span className="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{__('general.musoftware')}</span>
                    </Link>
                </div>

                {/* Centered Compact Auth Card Anchor */}
                <div ref={containerRef} className="w-full max-w-[420px] mx-auto bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl shadow-xs p-8 sm:p-10 transition-all">
                    {children}
                </div>
            </div>
            <FloatingWhatsAppButton />
        </div>
    );
}
