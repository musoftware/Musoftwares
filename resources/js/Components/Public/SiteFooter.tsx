import { usePage } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';

type FooterLink = { url: string; label: string; external: boolean };
export type SiteFooterData = {
    brand: string;
    tagline: string;
    columns: { title: string; links: FooterLink[] }[];
    social: { url: string; label: string }[];
    copyright: string;
    language_switch: { url: string; label: string };
    mobile_bar: Record<'pricing' | 'whatsapp' | 'account', { url: string; label: string }>;
};

/**
 * The same public footer the Blade pages show (resources/views/layouts/site-footer.blade.php).
 * Content comes from App\Support\SiteNav::footer() through Inertia, so both stay identical.
 */
export default function SiteFooter() {
    const { siteFooter: footer } = usePage().props as unknown as { siteFooter: SiteFooterData };

    return (
        <footer className="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-12 pb-10 px-6 sm:px-12 text-[#515154] text-[14px]">
            <div className="max-w-[1100px] mx-auto">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <div className="lg:col-span-4">
                        <p className="text-[18px] font-bold text-[#1d1d1f]">{footer.brand}</p>
                        <p className="mt-3 leading-[1.9]">{footer.tagline}</p>
                        <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px]">
                            {footer.social.map((social) => (
                                <a key={social.url} href={social.url} target="_blank" rel="noopener noreferrer" className="hover:text-[#0071e3] transition-colors">
                                    {social.label}
                                </a>
                            ))}
                        </div>
                    </div>
                    <div className="lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                        {footer.columns.map((column) => (
                            <div key={column.title}>
                                <h3 className="text-[14px] font-semibold text-[#1d1d1f]">{column.title}</h3>
                                <ul className="mt-3 space-y-2.5">
                                    {column.links.map((link) => (
                                        <li key={link.url}>
                                            {link.external || link.url.startsWith('mailto:') ? (
                                                <a href={link.url} {...(link.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})} className="hover:text-[#0071e3] transition-colors break-words">
                                                    {link.label}
                                                </a>
                                            ) : (
                                                <SafeLink href={link.url} className="hover:text-[#0071e3] transition-colors break-words">
                                                    {link.label}
                                                </SafeLink>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mt-10 pt-6 border-t border-black/10 flex flex-col md:flex-row items-center justify-between gap-3 text-[13px] text-[#86868b]">
                    <p>{footer.copyright}</p>
                    {/* Full page load: the locale is resolved server-side from ?lang. */}
                    <p><a href={footer.language_switch.url} className="hover:text-[#1d1d1f] transition-colors">{footer.language_switch.label}</a></p>
                </div>
            </div>
        </footer>
    );
}
