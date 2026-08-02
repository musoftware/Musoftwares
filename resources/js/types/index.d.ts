export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role?: string;
    roles?: string[];
    permissions?: string[];
    crm_features?: string[];
    enable_3d_dashboard?: boolean;
    openai_api_key?: string;
    openai_model?: string;
    gemini_api?: string;
    gemini_model?: string;
    default_ai_model?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
