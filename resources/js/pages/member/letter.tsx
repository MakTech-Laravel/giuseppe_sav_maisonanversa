import { Head } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';
import { Button } from '@/components/ui/button';

type Preferences = {
    heritageLetter: boolean;
    productUpdates: boolean;
    events: boolean;
};

export default function MemberLetter({
    preferences,
}: {
    preferences: Preferences;
}) {
    const { t } = useTranslation();
    const [data, setData] = useState(preferences);
    const [saved, setSaved] = useState(false);

    function onSubmit(event: FormEvent) {
        event.preventDefault();
        setSaved(true);
    }

    const options = [
        ['heritageLetter', t('Heritage Letter')] as const,
        ['productUpdates', t('Productupdates')] as const,
        ['events', t('Sessies & events')] as const,
    ];

    return (
        <>
            <Head title={t('Heritage Letter')} />
            <MemberPageHeader
                eyebrow={t('Correspondentie')}
                title={t('Heritage Letter-voorkeuren')}
                description={t(
                    'Kies wat u bereikt. Wijzigingen zijn prototype tot de lijstprovider is aangesloten.',
                )}
            />

            <MemberPanel className="max-w-xl">
                <form onSubmit={onSubmit} className="space-y-5">
                    {options.map(([key, label]) => (
                        <label
                            key={key}
                            className="flex cursor-pointer items-center gap-3"
                        >
                            <input
                                type="checkbox"
                                checked={data[key]}
                                onChange={(event) => {
                                    setSaved(false);
                                    setData((current) => ({
                                        ...current,
                                        [key]: event.target.checked,
                                    }));
                                }}
                                className="size-4 accent-gold"
                            />
                            <span className="font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                {label}
                            </span>
                        </label>
                    ))}

                    <Button type="submit" className="mt-4">
                        {t('Voorkeuren opslaan')}
                    </Button>

                    {saved && (
                        <p className="font-sans text-[11px] tracking-[0.12em] text-gold2 uppercase">
                            {t(
                                'Voorkeuren genoteerd voor deze sessie (prototype)',
                            )}
                        </p>
                    )}
                </form>
            </MemberPanel>
        </>
    );
}
