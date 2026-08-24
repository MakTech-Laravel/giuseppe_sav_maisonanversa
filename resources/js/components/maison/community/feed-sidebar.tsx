import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';

type FeedSidebarProps = {
    onViewEvents: () => void;
};

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return 'MA';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
}

export function FeedSidebar({ onViewEvents }: FeedSidebarProps) {
    const { t } = useTranslation();
    const { auth, sidebar } = usePage<{
        auth: { user: { name: string } | null };
        sidebar: {
            profile: {
                editionNumber: string;
                postCount: number;
                sessionCount: number;
            };
            recentMembers: Array<{
                name: string;
                city: string;
                editionNumber: string;
            }>;
            nextEvent: {
                title: string;
                location: string;
                startsAt: string;
            } | null;
        };
    }>().props;
    const name = auth?.user?.name ?? t('Yusuf Savran');
    const initials = initialsFromName(name);

    return (
        <aside className="space-y-5">
            <div className="border border-gold/15 bg-cream2 p-6">
                <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.25em] text-gold2 uppercase">
                    {t('Uw Profiel')}
                </span>

                <div className="flex items-center gap-3.5 border-b border-gold/10 pb-4">
                    <Monogram
                        initials={initials}
                        emphasis
                        className="size-12 text-lg"
                    />
                    <div>
                        <div className="font-serif text-base font-medium text-choc">
                            {name}
                        </div>
                        <div className="font-sans text-[9px] tracking-[0.15em] text-gold uppercase">
                            {t('Founding Member · Nr. {{num}}', { num: sidebar.profile.editionNumber })}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-3 pt-3.5 text-center">
                    {[
                        { value: sidebar.profile.editionNumber, label: 'Nummer' },
                        { value: String(sidebar.profile.postCount), label: 'Posts' },
                        { value: String(sidebar.profile.sessionCount), label: 'Sessies' },
                    ].map((stat) => (
                        <div key={stat.label}>
                            <div className="font-serif text-xl font-light text-gold2">
                                {stat.value}
                            </div>
                            <div className="font-sans text-[8px] tracking-[0.15em] text-stone uppercase">
                                {t(stat.label)}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="border border-gold/15 bg-cream2 p-6">
                <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.25em] text-gold2 uppercase">
                    {t('Recente Founding Members')}
                </span>

                {sidebar.recentMembers.map((member) => (
                    <div
                        key={`${member.name}-${member.editionNumber}`}
                        className="flex items-center gap-3 border-b border-gold/10 py-2.5"
                    >
                        <Monogram initials={initialsFromName(member.name)} size="sm" />
                        <div className="flex-1">
                            <div className="font-serif text-[15px] font-medium text-choc">
                                {member.name}
                            </div>
                            <div className="font-sans text-[9px] tracking-widest text-stone">
                                {t(member.city)}
                            </div>
                        </div>
                        <div className="font-serif text-[13px] text-gold2">
                            {member.editionNumber}
                        </div>
                    </div>
                ))}

                <div className="flex items-center gap-3 pt-2.5">
                    <Monogram
                        initials="+"
                        size="sm"
                        className="border-dashed text-stone"
                    />
                    <div>
                        <div className="font-serif text-[15px] font-medium text-stone">
                            {t('Founding Circle')}
                        </div>
                        <div className="font-sans text-[9px] tracking-widest text-stone">
                            {t('Bekijk alle leden')}
                        </div>
                    </div>
                </div>
            </div>

            <div className="border border-gold/15 bg-cream2 p-6">
                <span className="mb-4 block font-sans text-[9px] font-medium tracking-[0.25em] text-gold2 uppercase">
                    {t('Volgende Event')}
                </span>

                <div className="mb-3 bg-choc2 p-4">
                    <div className="mb-1.5 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                        {sidebar.nextEvent?.startsAt ? t(sidebar.nextEvent.startsAt) : t('Nog geen event')}
                    </div>
                    <div className="mb-1 font-serif text-base font-medium text-cream">
                        {sidebar.nextEvent
                            ? t(sidebar.nextEvent.title)
                            : t('Wordt binnenkort bekendgemaakt')}
                    </div>
                    <div className="font-sans text-[9px] tracking-widest text-stone">
                        {sidebar.nextEvent ? t(sidebar.nextEvent.location) : t('Maison Anversa')}
                    </div>
                </div>

                <button
                    type="button"
                    onClick={onViewEvents}
                    className="w-full cursor-pointer bg-choc px-6 py-3 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                >
                    {t('Bekijk alle events →')}
                </button>
            </div>
        </aside>
    );
}
