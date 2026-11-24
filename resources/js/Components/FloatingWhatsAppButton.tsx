import React, { useState, useEffect } from 'react';
import { MessageCircle } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { STUDIO_PHONE, whatsAppLink } from '@/lib/whatsapp';

interface FloatingWhatsAppButtonProps {
    phoneNumber?: string;
    defaultMessage?: string;
    className?: string;
}

/**
 * Floating WhatsApp link. The ready message is written in the visitor's voice
 * (general.whatsapp_float_default_msg), in the page language.
 */
export default function FloatingWhatsAppButton({
    phoneNumber,
    defaultMessage,
    className = "",
}: FloatingWhatsAppButtonProps) {
    const { settings } = usePage<any>().props || {};
    const [isVisible, setIsVisible] = useState(false);

    const phone = phoneNumber || settings?.business_whatsapp || settings?.business_phone || STUDIO_PHONE;
    const message = defaultMessage || __('general.whatsapp_float_default_msg');
    const label = __('general.contact_on_whatsapp');

    useEffect(() => {
        const timer = setTimeout(() => setIsVisible(true), 600);
        return () => clearTimeout(timer);
    }, []);

    if (!isVisible) return null;

    return (
        <a
            href={whatsAppLink(message, phone)}
            target="_blank"
            rel="noopener noreferrer"
            className={`fixed bottom-6 end-6 z-50 flex items-center gap-3 bg-[#25D366] hover:bg-[#1DA851] text-white py-3 px-5 rounded-full shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl group border border-emerald-400/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 ${className}`}
            aria-label={label}
        >
            <MessageCircle className="w-6 h-6" aria-hidden="true" />
            <span className="font-semibold text-xs sm:text-sm whitespace-nowrap overflow-hidden max-w-0 group-hover:max-w-xs group-focus-visible:max-w-xs transition-all duration-500 ease-in-out">
                {label}
            </span>
        </a>
    );
}
