import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import FrontendLayout from '@/layouts/frontend-layout';
import MemberLayout from '@/layouts/member-layout';
import SettingsLayout from '@/layouts/settings/layout';

/**
 * Shared between the browser bundle and the SSR entry so a page cannot pick up
 * a different shell depending on how it was rendered.
 */
export function resolvePageLayout(name: string) {
    switch (true) {
        case name === 'welcome':
            return null;
        case name.startsWith('maison/'):
        case name.startsWith('errors/'):
            return FrontendLayout;
        case name.startsWith('member/'):
            return MemberLayout;
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
}
