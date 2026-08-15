import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { MessageCircle, LayoutGrid, User, Sparkles, Send } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface MobileBottomActionBarProps {
    phoneNumber?: string;
    onOpenTicket?: () => void;
}

export default function MobileBottomActionBar({
    phoneNumber = "201015218548",
    onOpenTicket,
}: MobileBottomActionBarProps) {
    const { auth } = usePage<any>().props || {};
    const [isVisible, setIsVisible] = useState(true);
    const [lastScrollY, setLastScrollY] = useState(0);

    useEffect(() => {
        const handleScroll = () => {
            const currentScrollY = window.scrollY;
            if (currentScrollY > 100 && currentScrollY > lastScrollY + 15) {
                // Scrolling down fast -> hide
                setIsVisible(false);
            } else if (currentScrollY < lastScrollY - 10) {
                // Scrolling up -> show
                setIsVisible(true);
            }
            setLastScrollY(currentScrollY);
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, [lastScrollY]);

    const openWhatsApp = () => {
        const cleanPhone = phoneNumber.replace(/[^0-9]/g, '');
        const msg = encodeURIComponent(__('general.whatsapp_float_default_msg') || "Hello Mahmoud, I'd like to discuss a project with Musoftware!");
        window.open(`https://wa.me/${cleanPhone}?text=${msg}`, '_blank');
    };

    return (
        <div
            className={`lg:hidden fixed bottom-0 inset-x-0 z-40 px-4 pb-4 pt-2 bg-white/90 backdrop-blur-xl border-t border-zinc-200/80 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] transition-transform duration-300 ${
                isVisible ? 'translate-y-0' : 'translate-y-full'
            }`}
        >
            <div className="max-w-md mx-auto flex items-center justify-between gap-2">
                {/* 1. Explore Tools / Ecosystem */}
                <Link
                    href="/pricing"
                    className="flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold"
                >
                    <LayoutGrid className="w-5 h-5 mb-0.5 text-zinc-700" />
                    <span>{__('general.pricing') || 'Plans'}</span>
                </Link>

                {/* 2. WhatsApp Direct Action (Hero Button) */}
                <button
                    type="button"
                    onClick={openWhatsApp}
                    className="flex-[2] flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-zinc-950 text-white hover:bg-zinc-800 active:scale-95 transition shadow-sm text-xs font-bold uppercase tracking-wider cursor-pointer"
                >
                    <MessageCircle className="w-4 h-4 text-emerald-400 fill-emerald-400/20" />
                    <span>{__('general.chat_whatsapp') || 'WhatsApp'}</span>
                </button>

                {/* 3. Account / Login / Dashboard */}
                {auth?.user ? (
                    <Link
                        href="/dashboard"
                        className="flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold"
                    >
                        <User className="w-5 h-5 mb-0.5 text-zinc-700" />
                        <span>{__('general.dashboard') || 'Account'}</span>
                    </Link>
                ) : (
                    <Link
                        href="/login"
                        className="flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold"
                    >
                        <User className="w-5 h-5 mb-0.5 text-zinc-700" />
                        <span>{__('general.login') || 'Login'}</span>
                    </Link>
                )}
            </div>
        </div>
    );
}
