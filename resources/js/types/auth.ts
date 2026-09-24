export type User = {
    id: number;
    name: string;
    email: string;
    shipping_address?: string | null;
    shipping_city?: string | null;
    shipping_state?: string | null;
    shipping_zip?: string | null;
    shipping_country?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
