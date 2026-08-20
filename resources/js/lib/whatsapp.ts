export const STUDIO_PHONE = "201015218548";

export function openWhatsAppChat(message: string): void {
    const encoded = encodeURIComponent(message);
    window.open(`https://wa.me/${STUDIO_PHONE}?text=${encoded}`, '_blank');
}
