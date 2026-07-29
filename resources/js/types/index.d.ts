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
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
