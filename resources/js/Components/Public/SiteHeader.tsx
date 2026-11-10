import { useCallback, useEffect, useRef, useState, type RefObject } from 'react';
import { usePage } from '@inertiajs/react';
import { ChevronDown, ChevronLeft, ChevronRight, Menu, X, MessageCircle } from 'lucide-react';
import SafeLink from '@/Components/SafeLink';
import { __ } from '@/lib/i18n';

type NavLink = { url: string; label: string; active: boolean };
type MegaGroup = { key: string; title: string; url: string | null; active: boolean; links: NavLink[]; more: { url: string; label: string } | null };
type StoreCard = { key: string; name: string; line: string; url: string; image: string; active: boolean };
type Labels = { services: string; stores: string; stores_note: string; stores_cta: string; contact_url: string };
type SiteNav = { all_url: string; labels: Labels; intro: { text: string; cta: string; url: string }; main: NavLink[]; mega: MegaGroup[]; stores: StoreCard[] };
type OpenMenu = 'services' | 'stores' | null;
type AccountLink = { url: string; label: string };

const LOGO_PATH = 'M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z';
const WHATSAPP_URL = 'https://wa.me/201015218548';

const linkClass = (link: NavLink) => (link.active ? 'text-[#0071e3] font-semibold' : 'text-[#1d1d1f]/75 hover:text-[#0071e3]');
const panelClass = 'absolute inset-x-0 top-full max-h-[calc(100vh-4rem)] overflow-y-auto whitespace-normal border-b border-black/10 bg-white shadow-lg';

function MenuButton({ label, active, open, onClick }: { label: string; active: boolean; open: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-expanded={open}
            className={`flex items-center gap-1 py-2 transition-colors ${active ? 'font-semibold text-[#0071e3]' : 'text-[#1d1d1f]/75 hover:text-[#0071e3]'}`}
        >
            {label}
            <ChevronDown className={`h-3.5 w-3.5 transition-transform ${open ? 'rotate-180' : ''}`} />
        </button>
    );
}

function IntroBox({ intro, stacked }: { intro: SiteNav['intro']; stacked: boolean }) {
    return (
        <div className={`rounded-xl bg-[#f5f5f7] ${stacked ? 'p-4' : 'flex items-center justify-between gap-6 px-5 py-4'}`}>
            <p className="max-w-[760px] text-[14px] font-normal leading-relaxed text-[#1d1d1f]">{intro.text}</p>
            <SafeLink
                href={intro.url}
                className={`flex min-h-[44px] shrink-0 items-center justify-center rounded-lg bg-[#0071e3] px-5 text-[14px] font-semibold text-white transition-colors hover:bg-[#0077ed] ${stacked ? 'mt-3' : ''}`}
            >
                {intro.cta}
            </SafeLink>
        </div>
    );
}

function GroupLinks({ group, onNavigate, mobile }: { group: MegaGroup; onNavigate?: () => void; mobile?: boolean }) {
    const itemClass = mobile ? 'flex min-h-[44px] items-center text-[14px]' : 'block break-inside-avoid py-2 text-[14px] font-normal leading-snug transition-colors';

    return (
        <>
            <div className={mobile ? '' : 'columns-2 gap-6 xl:columns-3'}>
                {group.links.map((link) => (
                    <SafeLink key={link.url} href={link.url} onClick={onNavigate} className={`${itemClass} ${linkClass(link)}`}>
                        {link.label}
                    </SafeLink>
                ))}
            </div>
            {group.more && (
                <SafeLink href={group.more.url} onClick={onNavigate} className={`${mobile ? 'flex min-h-[44px] items-center' : 'mt-3 inline-block hover:underline'} text-[14px] font-semibold text-[#0071e3]`}>
                    {group.more.label}
                </SafeLink>
            )}
        </>
    );
}

function ServicesPanel({ nav }: { nav: SiteNav }) {
    const [current, setCurrent] = useState(nav.mega.find((group) => group.active)?.key ?? nav.mega[0]?.key);
    const isRtl = typeof document !== 'undefined' && document.documentElement.dir === 'rtl';
    const Arrow = isRtl ? ChevronLeft : ChevronRight;
    const group = nav.mega.find((item) => item.key === current) ?? nav.mega[0];

    return (
        <div className={panelClass}>
            <div className="mx-auto max-w-[1240px] px-8 py-6">
                <IntroBox intro={nav.intro} stacked={false} />
                <div className="mt-5 grid grid-cols-[250px_1fr] gap-8">
                    <div role="tablist" aria-orientation="vertical" aria-label={nav.labels.services} className="border-e border-black/5 pe-4">
                        {nav.mega.map((item) => (
                            <button
                                key={item.key}
                                type="button"
                                role="tab"
                                aria-selected={item.key === group.key}
                                onClick={() => setCurrent(item.key)}
                                onFocus={() => setCurrent(item.key)}
                                onMouseEnter={() => setCurrent(item.key)}
                                className={`flex min-h-[44px] w-full items-center justify-between gap-2 rounded-lg px-3 text-start text-[14px] transition-colors hover:bg-[#f5f5f7] ${item.key === group.key ? 'bg-[#f5f5f7] font-semibold text-[#0071e3]' : 'font-medium text-[#1d1d1f]/80'}`}
                            >
                                {item.title}
                                <Arrow className="h-4 w-4 shrink-0" />
                            </button>
                        ))}
                    </div>
                    <div role="tabpanel">
                        <p className="pb-2 text-[13px] font-semibold text-[#1d1d1f]/60">{group.title}</p>
                        <GroupLinks group={group} />
                    </div>
                </div>
            </div>
        </div>
    );
}

function StoresPanel({ nav }: { nav: SiteNav }) {
    return (
        <div className={panelClass}>
            <div className="mx-auto max-w-[1240px] px-8 py-6">
                <div className="grid grid-cols-4 gap-6">
                    {nav.stores.map((store) => (
                        <SafeLink key={store.key} href={store.url} className="group/store block">
                            <img src={store.image} alt="" width={640} height={400} loading="lazy" className="aspect-[16/10] w-full rounded-xl border border-black/5 bg-[#f5f5f7] object-cover transition-opacity group-hover/store:opacity-90" />
                            <span className={`block pt-3 text-[16px] font-semibold ${store.active ? 'text-[#0071e3]' : 'text-[#1d1d1f] group-hover/store:text-[#0071e3]'}`}>{store.name}</span>
                            <span className="block pt-1 text-[13px] font-normal leading-relaxed text-[#1d1d1f]/65">{store.line}</span>
                        </SafeLink>
                    ))}
                </div>
                <p className="mt-6 border-t border-black/5 pt-4 text-[14px] font-normal text-[#1d1d1f]/75">
                    {nav.labels.stores_note}{' '}
                    <SafeLink href={nav.labels.contact_url} className="font-semibold text-[#0071e3] hover:underline">
                        {nav.labels.stores_cta}
                    </SafeLink>
                </p>
            </div>
        </div>
    );
}

function MobileStores({ nav, onNavigate }: { nav: SiteNav; onNavigate: () => void }) {
    return (
        <>
            <p className="pb-1 pt-5 text-[13px] font-semibold text-[#1d1d1f]/60">{nav.labels.stores}</p>
            {nav.stores.map((store) => (
                <SafeLink key={store.key} href={store.url} onClick={onNavigate} className="flex items-center gap-3 border-b border-black/5 py-2.5">
                    <img src={store.image} alt="" width={80} height={50} loading="lazy" className="h-[50px] w-20 shrink-0 rounded-lg border border-black/5 bg-[#f5f5f7] object-cover" />
                    <span className="min-w-0">
                        <span className={`block text-[15px] font-semibold ${store.active ? 'text-[#0071e3]' : 'text-[#1d1d1f]'}`}>{store.name}</span>
                        <span className="block text-[13px] font-normal leading-snug text-[#1d1d1f]/65">{store.line}</span>
                    </span>
                </SafeLink>
            ))}
        </>
    );
}

function MobileMenu({ nav, account, contactLabel, onNavigate }: { nav: SiteNav; account: AccountLink; contactLabel: string; onNavigate: () => void }) {
    return (
        <div className="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-black/5 bg-white px-5 py-4 text-[15px] font-medium text-[#1d1d1f] lg:hidden">
            <IntroBox intro={nav.intro} stacked />
            <p className="pb-1 pt-5 text-[13px] font-semibold text-[#1d1d1f]/60">{nav.labels.services}</p>
            {nav.mega.map((group) => (
                <details key={group.key} className="group border-b border-black/5" open={group.active}>
                    <summary className="flex min-h-[48px] cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                        {group.title}
                        <ChevronDown className="h-4 w-4 shrink-0 transition-transform group-open:rotate-180" />
                    </summary>
                    <div className="pb-2 ps-3">
                        <GroupLinks group={group} onNavigate={onNavigate} mobile />
                    </div>
                </details>
            ))}
            <MobileStores nav={nav} onNavigate={onNavigate} />
            <div className="pt-3">
                {nav.main.map((link) => (
                    <SafeLink key={link.url} href={link.url} onClick={onNavigate} className={`flex min-h-[48px] items-center border-b border-black/5 ${linkClass(link)}`}>
                        {link.label}
                    </SafeLink>
                ))}
            </div>
            <div className="flex items-center gap-3 pt-4">
                <SafeLink href={account.url} onClick={onNavigate} className="flex min-h-[44px] flex-1 items-center justify-center rounded-lg border border-black/10 text-sm font-semibold transition-colors hover:bg-[#f5f5f7]">
                    {account.label}
                </SafeLink>
                <SafeLink href="/company/contact" onClick={onNavigate} className="flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-[#0071e3] text-sm font-semibold text-white transition-colors hover:bg-[#0077ed]">
                    {contactLabel}
                </SafeLink>
            </div>
        </div>
    );
}

function useCloseOutside(ref: RefObject<HTMLElement | null>, close: () => void) {
    useEffect(() => {
        const onClick = (event: MouseEvent) => {
            if (ref.current && !ref.current.contains(event.target as Node)) close();
        };
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') close();
        };
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [ref, close]);
}

function Logo() {
    return (
        <SafeLink href="/" className="flex shrink-0 items-center gap-2.5 focus:outline-none" title="Musoftware">
            <svg className="h-6 w-6 fill-[#0071e3]" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d={LOGO_PATH} />
            </svg>
            <span className="text-[20px] font-bold tracking-tight text-[#1d1d1f]">Musoftware</span>
        </SafeLink>
    );
}

/** The same public header the Blade pages show. The links come from App\Support\SiteNav through Inertia. */
export default function SiteHeader() {
    const page = usePage();
    const { siteNav, auth, locale } = page.props as unknown as { siteNav: SiteNav; auth?: { user?: unknown }; locale: string };
    const isAr = locale === 'ar';
    const contactLabel = isAr ? 'تواصل معنا' : 'Contact us';
    const [openMenu, setOpenMenu] = useState<OpenMenu>(null);
    const [mobileOpen, setMobileOpen] = useState(false);
    const navRef = useRef<HTMLElement>(null);
    const toggle = (menu: Exclude<OpenMenu, null>) => setOpenMenu((open) => (open === menu ? null : menu));
    const closeMenus = useCallback(() => setOpenMenu(null), []);
    useCloseOutside(navRef, closeMenus);
    useEffect(closeMenus, [page.url, closeMenus]);

    const account: AccountLink = auth?.user ? { url: '/dashboard', label: isAr ? 'لوحة التحكم' : 'Dashboard' } : { url: '/login', label: isAr ? 'تسجيل الدخول' : 'Sign in' };

    return (
        <header className="sticky top-0 z-50 w-full border-b border-black/5 bg-white/90 backdrop-blur-md">
            <div className="mx-auto flex h-16 max-w-[1240px] items-center justify-between gap-4 px-4 sm:px-8">
                <Logo />
                <nav ref={navRef} className="hidden items-center gap-4 whitespace-nowrap text-[14px] font-medium lg:flex xl:gap-7" aria-label={isAr ? 'القائمة الرئيسية' : 'Main menu'}>
                    <MenuButton label={siteNav.labels.services} active={siteNav.mega.some((group) => group.active)} open={openMenu === 'services'} onClick={() => toggle('services')} />
                    {openMenu === 'services' && <ServicesPanel nav={siteNav} />}
                    <MenuButton label={siteNav.labels.stores} active={siteNav.stores.some((store) => store.active)} open={openMenu === 'stores'} onClick={() => toggle('stores')} />
                    {openMenu === 'stores' && <StoresPanel nav={siteNav} />}
                    {siteNav.main.map((link) => (
                        <SafeLink key={link.url} href={link.url} className={`transition-colors ${linkClass(link)}`}>
                            {link.label}
                        </SafeLink>
                    ))}
                </nav>

                <div className="flex shrink-0 items-center gap-2 sm:gap-4">
                    <a href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer" className="hidden p-2 text-[#1d1d1f]/70 transition-colors hover:text-[#25D366] lg:inline-flex" title={__('general.whatsapp_direct')} aria-label="WhatsApp">
                        <MessageCircle className="h-5 w-5" />
                    </a>
                    <SafeLink href={account.url} className="hidden text-[14px] font-medium text-[#1d1d1f]/75 transition-colors hover:text-[#0071e3] lg:inline-flex">
                        {account.label}
                    </SafeLink>
                    <SafeLink href="/company/contact" className="hidden items-center justify-center rounded-lg bg-[#0071e3] px-5 py-2 text-[14px] font-medium text-white shadow-sm transition-colors hover:bg-[#0077ed] sm:inline-flex lg:hidden xl:inline-flex">
                        {contactLabel}
                    </SafeLink>
                    <button type="button" onClick={() => setMobileOpen((open) => !open)} className="p-2 text-[#1d1d1f] transition-colors hover:text-[#0071e3] lg:hidden" aria-label={isAr ? 'فتح القائمة' : 'Open menu'}>
                        {mobileOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>
            </div>

            {mobileOpen && <MobileMenu nav={siteNav} account={account} contactLabel={contactLabel} onNavigate={() => setMobileOpen(false)} />}
        </header>
    );
}
