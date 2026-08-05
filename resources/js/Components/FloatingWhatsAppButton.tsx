import React, { useState, useEffect } from 'react';
import { MessageCircle } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

interface FloatingWhatsAppButtonProps {
    phoneNumber?: string;
    defaultMessage?: string;
    className?: string;
}

export default function FloatingWhatsAppButton({
    phoneNumber,
    defaultMessage,
    className = "",
}: FloatingWhatsAppButtonProps) {
    const { settings } = usePage<any>().props || {};
    const [isVisible, setIsVisible] = useState(false);

    const phone = phoneNumber || settings?.business_whatsapp || settings?.business_phone || "201015218548";
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const message = defaultMessage || __('general.whatsapp_float_default_msg') || "Mahmoud here 👋 Send me your project details";

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsVisible(true);
        }, 600);
        return () => clearTimeout(timer);
    }, []);

    const handleClick = () => {
        const encodedMessage = encodeURIComponent(message);
        window.open(`https://wa.me/${cleanPhone}?text=${encodedMessage}`, '_blank');
    };

    if (!isVisible) return null;

    return (
        <button
            onClick={handleClick}
            type="button"
            className={`fixed bottom-6 end-6 z-50 flex items-center gap-3 bg-[#25D366] hover:bg-[#1DA851] text-white py-3 px-5 rounded-full shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl group border border-emerald-400/30 cursor-pointer ${className}`}
            aria-label={__('general.contact_on_whatsapp') || "Contact on WhatsApp"}
        >
            <div className="relative flex items-center justify-center">
                <MessageCircle className="w-6 h-6 animate-pulse" />
                <span className="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-200"></span>
                </span>
            </div>
            <span className="font-semibold text-xs sm:text-sm whitespace-nowrap overflow-hidden max-w-0 group-hover:max-w-xs transition-all duration-500 ease-in-out">
                {message}
            </span>
        </button>
    );
}
