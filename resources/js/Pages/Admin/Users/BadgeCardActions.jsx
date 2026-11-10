import React, { useState } from 'react';
import { Check, Copy, ExternalLink, MessageCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { whatsAppLink } from '@/lib/whatsapp';

/** Translated level name ("Gold" / "ذهبي"), falling back to the stored name. */
function tierName(tier) {
    const slug = (tier?.slug || 'bronze').toLowerCase();
    const key = `client_home.tiers.${slug}`;
    const text = __(key);
    return text !== key ? text : tier?.name || slug;
}

/**
 * Lets the admin open the client's badge card, copy its link, or send it on WhatsApp with a ready message.
 */
export default function BadgeCardActions({ url, client }) {
    const [copied, setCopied] = useState(false);
    const firstName = (client.name || '').trim().split(' ')[0];
    const message = `${__('badge_card.admin_message', { name: firstName, tier: tierName(client.loyalty_tier) })} ${url}`;
    const phone = client.whatsapp_number || client.mobile_1 || client.mobile_2 || client.phone;

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(url);
        } catch {
            toast.error(url);
            return;
        }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="mt-4 flex flex-wrap items-center justify-center gap-2 md:justify-start">
            <span className="text-sm font-semibold text-slate-700">{__('badge_card.admin_title')}</span>
            <Button asChild size="sm" variant="outline">
                <a href={url} target="_blank" rel="noopener noreferrer">
                    <ExternalLink className="me-1.5 h-4 w-4" />{__('badge_card.open')}
                </a>
            </Button>
            <Button size="sm" variant="outline" onClick={copy}>
                {copied ? <Check className="me-1.5 h-4 w-4" /> : <Copy className="me-1.5 h-4 w-4" />}
                {copied ? __('badge_card.copied') : __('badge_card.copy')}
            </Button>
            <Button asChild size="sm" variant="outline">
                <a href={whatsAppLink(message, phone)} target="_blank" rel="noopener noreferrer">
                    <MessageCircle className="me-1.5 h-4 w-4" />{__('badge_card.whatsapp')}
                </a>
            </Button>
        </div>
    );
}
