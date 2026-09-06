import type { PermissionKey } from './permissions';

export type User = {
    id: number;
    name: string;
    username: string;
    gender?: string | null;
    email: string;
    avatar?: string | null;
    avatar_url?: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    roles: string[];
    permissions: PermissionKey[];
    is_super_admin: boolean;
    is_admin?: boolean;
    is_founding_circle?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
