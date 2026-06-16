import { MessageCircle } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function FloatingWhatsAppButton({ phoneNumber = "201015218548", defaultMessage = "Mahmoud here 👋 Send me your project details" }) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        // Slight delay before showing the button for a nice entrance effect
        const timer = setTimeout(() => {
            setIsVisible(true);
        }, 1000);
        return () => clearTimeout(timer);
    }, []);

    const handleClick = () => {
        const encodedMessage = encodeURIComponent(defaultMessage);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    if (!isVisible) return null;

    return (
        <button
            onClick={handleClick}
            className="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-[#25D366] hover:bg-[#1DA851] text-white py-3 px-5 rounded-full shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl group"
            aria-label="Contact on WhatsApp"
        >
            <MessageCircle className="w-6 h-6 animate-pulse" />
            <span className="font-semibold text-sm whitespace-nowrap overflow-hidden max-w-0 group-hover:max-w-xs transition-all duration-500 ease-in-out">
                {defaultMessage}
            </span>
        </button>
    );
}
