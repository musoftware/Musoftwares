export interface LinkedInSession {
    hasSession: boolean;
    isValid: boolean;
    validatedAt: string | null;
}

export interface B2BCampaign {
    id: string;
    name: string;
    keyword: string;
    country: string | null;
    city: string | null;
    sources: string[];
    daily_limit: number;
    status: 'draft' | 'running' | 'completed' | 'failed' | string;
}

export interface B2BLead {
    id: string;
    campaign_id: string;
    name: string;
    title: string;
    company: string;
    email: string | null;
    email_status: 'valid' | 'invalid' | 'catchall' | 'unknown' | string;
    lead_score: number;
    source: string;
    outreach_status: string;
}

export interface B2BInbox {
    id: string;
    email: string;
    smtp_host: string;
    smtp_port: number;
    smtp_user: string;
    smtp_pass: string;
    imap_host: string | null;
    imap_port: number | null;
    status: 'active' | 'failed' | string;
}

export interface B2BSequence {
    id?: string;
    campaign_id: string;
    name: string;
    step: number;
    subject: string;
    body: string;
    delay_days: number;
}

export interface RealtimeLog {
    id: string;
    message: string;
    time: string;
}

export interface B2BCampaignStats {
    total: number;
    valid: number;
    emailed: number;
}
