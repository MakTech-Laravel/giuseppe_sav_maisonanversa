import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { CommunityLayout } from '@/components/maison/community/community-layout';
import { CommunityLoginGate } from '@/components/maison/community/community-login-gate';
import {
    CommunityToast,
    useCommunityToast,
} from '@/components/maison/community/community-toast';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Community() {
    const { t } = useTranslation();
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const toast = useCommunityToast();

    function handleLogin() {
        setIsLoggedIn(true);
        toast.show(t('Welkom terug bij de Community.'));
    }

    return (
        <>
            <Head title="Community" />

            <PageHero
                eyebrow={t('Founding Circle & Club Corner')}
                title={
                    <>
                        {t('De')} <em>Community</em>
                    </>
                }
                subtitle={t(
                    'Een besloten ruimte voor Founding Circle leden en Club Corner partners. Deel uw ervaringen, plan sessies en ontdek exclusieve evenementen.',
                )}
            />

            {!isLoggedIn ? (
                <CommunityLoginGate onLogin={handleLogin} />
            ) : (
                <CommunityLayout toast={toast} />
            )}

            <CommunityToast message={toast.message} visible={toast.visible} />
        </>
    );
}
