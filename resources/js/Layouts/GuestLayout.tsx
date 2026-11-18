import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { __ } from '@/lib/i18n';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import ThemeToggle from '@/Components/ThemeToggle';

/**
 * Sign in, sign up and password pages. A quiet gray picture side on large screens,
 * and a plain white form side with lots of space. The form fades up once on load.
 */
export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen w-full overflow-x-clip bg-white font-sans text-[#111] dark:bg-zinc-950 dark:text-zinc-100">
            <ShowcasePanel />

            <main className="flex flex-1 flex-col px-4 py-6 sm:px-8 lg:px-16">
                <div className="flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2.5 lg:invisible">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#F8F8F8] p-1.5 dark:bg-zinc-900">
                            <ApplicationLogo className="h-full w-full object-contain" />
                        </span>
                        <span className="text-base font-semibold tracking-tight">Musoftware</span>
                    </Link>
                    <ThemeToggle className="h-9 w-9" />
                </div>

                <div className="flex flex-1 items-center justify-center py-10 sm:py-16">
                    <div className="calm-rise w-full max-w-[400px]">{children}</div>
                </div>
            </main>

            <FloatingWhatsAppButton />
        </div>
    );
}

function ShowcasePanel() {
    return (
        <aside className="hidden flex-col justify-between bg-[#F8F8F8] p-12 dark:bg-zinc-900 lg:flex lg:w-[42%] xl:w-[45%] xl:p-16">
            <Link href="/" className="flex items-center gap-3">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-2 dark:bg-zinc-800">
                    <ApplicationLogo className="h-full w-full object-contain" />
                </span>
                <span className="flex flex-col">
                    <span className="text-lg font-semibold tracking-tight">Musoftware</span>
                    <span className="text-xs text-[#555] dark:text-zinc-400">{__('frontend.guest_layout_tagline')}</span>
                </span>
            </Link>

            <figure className="mx-auto w-full max-w-sm space-y-6">
                <img
                    src="/images/illustrations/auth_showcase.jpg"
                    alt={__('frontend.guest_layout_showcase_alt')}
                    className="w-full rounded-[20px] object-cover"
                />
                <figcaption className="space-y-2 text-center">
                    <p className="text-[15px] font-semibold">{__('frontend.guest_layout_showcase_title')}</p>
                    <p className="text-sm leading-relaxed text-[#555] dark:text-zinc-400">{__('frontend.guest_layout_showcase_desc')}</p>
                </figcaption>
            </figure>

            <div className="flex items-center justify-between text-[13px] text-[#555] dark:text-zinc-400">
                <span>© {new Date().getFullYear()} Musoftware</span>
                <nav className="flex gap-6">
                    <Link href="/docs" className="hover:text-[#111] dark:hover:text-white">{__('general.help')}</Link>
                    <Link href="/privacy-policy" className="hover:text-[#111] dark:hover:text-white">{__('general.privacy')}</Link>
                    <Link href="/terms-of-service" className="hover:text-[#111] dark:hover:text-white">{__('general.terms')}</Link>
                </nav>
            </div>
        </aside>
    );
}
