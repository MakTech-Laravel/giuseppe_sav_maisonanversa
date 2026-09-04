import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { CommunityLayout } from '@/components/maison/community/community-layout';
import { CommunityLoginGate } from '@/components/maison/community/community-login-gate';
import {
    CommunityToast,
    useCommunityToast,
} from '@/components/maison/community/community-toast';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import type { Paginated } from '@/types/admin';

type CommunityPageProps = {
    posts?: Paginated<FeedPostData>;
};

export default function Community({ posts }: CommunityPageProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const toast = useCommunityToast();
    const isAuthenticated = auth?.user != null;

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Founding Circle')}
                title={t('De Community')}
                subtitle={t(
                    'Een besloten ruimte voor leden van het huis. Deel uw ervaringen, plan sessies en ontdek exclusieve evenementen.',
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
