export const STUDIO_PHONE = "201015218548";

export function openWhatsAppChat(message: string): void {
    const encoded = encodeURIComponent(message);
    window.open(`https://wa.me/${STUDIO_PHONE}?text=${encoded}`, '_blank');
}

/** Egypt's country code, used when a saved number is written the local way (01xxxxxxxxx). */
const LOCAL_COUNTRY_CODE = '20';

/** wa.me wants digits with the country code and no leading zeros. */
function toWhatsAppNumber(phone: string): string {
    const digits = phone.replace(/[^0-9]/g, '');
    if (digits.startsWith('00')) return digits.slice(2);
    if (digits.startsWith('0')) return LOCAL_COUNTRY_CODE + digits.slice(1);
    return digits;
}

/**
 * A WhatsApp link with a ready message. Without a phone number, WhatsApp asks which chat to send it to.
 */
export function whatsAppLink(message: string, phone?: string | null): string {
    const number = phone ? toWhatsAppNumber(phone) : '';
    return `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
}
