import { Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { ClubSearchField } from '@/components/maison/community/sessions/club-search-field';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { SessionDraftPreview } from '@/components/maison/community/sessions/session-draft-preview';
import { SessionScheduleFields } from '@/components/maison/community/sessions/session-schedule-fields';
import {
    ChoiceGroup,
    SessionStep,
} from '@/components/maison/community/sessions/session-step';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import * as sessionRoutes from '@/routes/community/sessions';
import {
    combineDateAndTime,
    toDateInputValue,
} from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type {
    ClubCard,
    SessionFormOptions,
    SessionPlayer,
} from '@/types/session';

type SessionCreateProps = {
    options: SessionFormOptions;
    host: SessionPlayer;
};

/** Which fields belong to each mobile page of the composer. */
const MOBILE_PAGES: readonly (readonly string[])[] = [
    ['sport'],
    ['club_id'],
    ['starts_at', 'duration_minutes'],
    ['court_status'],
    ['level'],
    ['gender'],
    ['capacity'],
];

export default function SessionCreate({ options, host }: SessionCreateProps) {
    const { t } = useTranslation();
    const { locale, errors } = usePage().props;
    const [club, setClub] = useState<ClubCard | null>(null);
    const [step, setStep] = useState(0);

    // `starts_at` is assembled from the date and time fields at submit time,
    // so its error lives on the page rather than on the form.
    const startsAtError = errors.starts_at;

    const defaults = useMemo(() => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        return { date: toDateInputValue(tomorrow), time: '10:30' };
    }, []);

    const form = useForm({
        sport: options.sports[0]?.value ?? 'padel',
        club_id: null as number | null,
        date: defaults.date,
        time: defaults.time,
        duration_minutes: 90,
        court_status: 'not_booked',
        level: 'intermediate',
        gender: 'everyone',
        capacity: 4,
        notes: '',
    });

    function handleSelectClub(next: ClubCard | null) {
        setClub(next);
        form.setData('club_id', next?.id ?? null);
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((data) => ({
            sport: data.sport,
            club_id: data.club_id,
            starts_at: combineDateAndTime(data.date, data.time),
            duration_minutes: data.duration_minutes,
            court_status: data.court_status,
            level: data.level,
            gender: data.gender,
            capacity: data.capacity,
            notes: data.notes || null,
        }));

        form.post(sessionRoutes.store.url(locale));
    }

    const isLastStep = step === MOBILE_PAGES.length - 1;

    /** On mobile only the current page renders; `md` and up shows them all. */
    const visibility = (index: number) =>
        cn(step === index ? 'block' : 'hidden', 'md:block');

    const openSlots = Math.max(0, form.data.capacity - 1);

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Community')}
                title={t('Sessie aanmaken')}
                subtitle={t(
                    'Plan uw volgende partij. Ontmoet leden. Speel samen.',
                )}
            />

            <CommunityTabs />

            <div className="bg-cream py-12">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <form
                        onSubmit={handleSubmit}
                        className="border border-gold/15 bg-cream2"
                    >
                        <div className={visibility(0)}>
                            <SessionStep
                                index={1}
                                title={t('Sport')}
                                description={t('Kies de sport')}
                            >
                                <ChoiceGroup
                                    label={t('Sport')}
                                    options={options.sports}
                                    value={form.data.sport}
                                    onChange={(value) => {
                                        form.setData('sport', value);
                                        handleSelectClub(null);
                                    }}
                                    columns="grid-cols-2"
                                />
                            </SessionStep>
                        </div>

                        <div className={visibility(1)}>
                            <SessionStep
                                index={2}
                                title={t('Club')}
                                description={t('Zoek een club of corner')}
                            >
                                <ClubSearchField
                                    sport={form.data.sport}
                                    selected={club}
                                    onSelect={handleSelectClub}
                                    suggestions={options.partner_clubs}
                                    error={form.errors.club_id}
                                />
                            </SessionStep>
                        </div>

                        <div className={visibility(2)}>
                            <SessionStep
                                index={3}
                                title={t('Wanneer')}
                                description={t('Datum, tijd en duur')}
                            >
                                <SessionScheduleFields
                                    date={form.data.date}
                                    time={form.data.time}
                                    durationMinutes={form.data.duration_minutes}
                                    durations={options.durations}
                                    locale={locale}
                                    onDateChange={(value) =>
                                        form.setData('date', value)
                                    }
                                    onTimeChange={(value) =>
                                        form.setData('time', value)
                                    }
                                    onDurationChange={(value) =>
                                        form.setData('duration_minutes', value)
                                    }
                                    error={startsAtError}
                                />
                            </SessionStep>
                        </div>

                        <div className={visibility(3)}>
                            <SessionStep
                                index={4}
                                title={t('Baanstatus')}
                                description={t('Is de baan al geboekt?')}
                            >
                                <ChoiceGroup
                                    label={t('Baanstatus')}
                                    options={options.court_statuses}
                                    value={form.data.court_status}
                                    onChange={(value) =>
                                        form.setData('court_status', value)
                                    }
                                    columns="grid-cols-1 sm:grid-cols-2"
                                />
                                <p className="mt-3 font-sans text-[11px] text-stone">
                                    {t(
                                        'De host is verantwoordelijk voor het boeken van de baan.',
                                    )}
                                </p>
                            </SessionStep>
                        </div>

                        <div className={visibility(4)}>
                            <SessionStep
                                index={5}
                                title={t('Niveau')}
                                description={t('Spelniveau')}
                            >
                                <ChoiceGroup
                                    label={t('Niveau')}
                                    options={options.levels}
                                    value={form.data.level}
                                    onChange={(value) =>
                                        form.setData('level', value)
                                    }
                                />
                            </SessionStep>
                        </div>

                        <div className={visibility(5)}>
                            <SessionStep
                                index={6}
                                title={t('Wie mag meespelen?')}
                                description={t('Wie mag deelnemen?')}
                            >
                                <ChoiceGroup
                                    label={t('Wie mag meespelen?')}
                                    options={options.genders}
                                    value={form.data.gender}
                                    onChange={(value) =>
                                        form.setData('gender', value)
                                    }
                                />
                            </SessionStep>
                        </div>

                        <div className={visibility(6)}>
                            <SessionStep
                                index={7}
                                title={t('Spelers')}
                                description={t(
                                    'U bent automatisch speler 1.',
                                )}
                            >
                                <ChoiceGroup
                                    label={t('Aantal spelers')}
                                    options={options.capacities.map(
                                        (capacity) => ({
                                            value: capacity,
                                            label: `${capacity}`,
                                        }),
                                    )}
                                    value={form.data.capacity}
                                    onChange={(value) =>
                                        form.setData('capacity', value)
                                    }
                                    translateLabels={false}
                                    columns="grid-cols-4"
                                />

                                <div className="mt-5">
                                    <PlayerSlots
                                        players={[host]}
                                        openSlots={openSlots}
                                        withLabels
                                    />
                                </div>

                                <label className="mt-6 flex flex-col gap-1.5">
                                    <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                        {t('Notitie (optioneel)')}
                                    </span>
                                    <input
                                        type="text"
                                        value={form.data.notes}
                                        onChange={(event) =>
                                            form.setData(
                                                'notes',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t(
                                            'Bijv. niveau, taal, bijzonderheden...',
                                        )}
                                        className={fieldClassName}
                                    />
                                    <span className="font-sans text-[11px] text-stone">
                                        {t(
                                            'Tekst op dit formulier is de bron. DeepL vult NL, EN en FR na opslaan.',
                                        )}
                                    </span>
                                </label>
                            </SessionStep>
                        </div>

                        <div className="flex flex-col gap-3 border-t border-gold/15 px-6 py-6 md:flex-row md:items-center md:justify-center md:px-8">
                            {/* Mobile walks the steps; desktop submits directly. */}
                            {!isLastStep && (
                                <button
                                    type="button"
                                    onClick={() => setStep(step + 1)}
                                    className="w-full bg-choc px-8 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 md:hidden"
                                >
                                    {t('Doorgaan')}
                                </button>
                            )}

                            {step > 0 && (
                                <button
                                    type="button"
                                    onClick={() => setStep(step - 1)}
                                    className="w-full border border-gold/30 px-8 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold md:hidden"
                                >
                                    {t('Terug')}
                                </button>
                            )}

                            <button
                                type="submit"
                                disabled={form.processing}
                                className={cn(
                                    'bg-choc px-10 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:opacity-50',
                                    isLastStep ? 'block w-full' : 'hidden',
                                    'md:block md:w-auto',
                                )}
                            >
                                {t('Sessie publiceren')}
                            </button>

                            <Link
                                href={sessionRoutes.index.url(locale)}
                                className="hidden border border-gold/30 px-10 py-4 text-center font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 md:block"
                            >
                                {t('Annuleren')}
                            </Link>
                        </div>

                        <p className="border-t border-gold/15 px-6 py-4 text-center font-sans text-[10px] tracking-[0.08em] text-stone md:px-8">
                            {t(
                                'Door een sessie aan te maken gaat u akkoord met onze communityrichtlijnen.',
                            )}
                        </p>
                    </form>

                    <SessionDraftPreview
                        sport={form.data.sport}
                        club={club}
                        startsAt={combineDateAndTime(
                            form.data.date,
                            form.data.time,
                        )}
                        durationMinutes={form.data.duration_minutes}
                        level={form.data.level}
                        courtStatus={form.data.court_status}
                        gender={form.data.gender}
                        capacity={form.data.capacity}
                        host={host}
                        options={options}
                        locale={locale}
                    />
                    </div>
                </Wrap>
            </div>
        </>
    );
}

const fieldClassName =
    'w-full border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';
