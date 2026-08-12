import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { CommunityLayout } from '@/components/maison/community/community-layout';
import { CommunityLoginGate } from '@/components/maison/community/community-login-gate';
import {
    CommunityToast,
    useCommunityToast,
} from '@/components/maison/community/community-toast';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { PageHero } from '@/components/maison/ui/page-hero';
import type { Paginated } from '@/types/admin';

type CommunityPageProps = {
    posts?: Paginated<FeedPostData>;
};

export default function Community({ posts }: CommunityPageProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const toast = useCommunityToast();
    const isAuthenticated = auth.user !== null;

    return (
        <>
            <MaisonSeoHead page="community" />

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

            {isAuthenticated && posts ? (
                <CommunityLayout toast={toast} posts={posts} />
            ) : (
                <CommunityLoginGate />
            )}

            <CommunityToast message={toast.message} visible={toast.visible} />
        </>
    );
}
