import { useMemo, useState, useSyncExternalStore } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Reveal } from '@/components/maison/ui/reveal';
import { useLocale } from '@/hooks/use-locale';
import {
    clearFcMember,
    formatEditionNumber,
    readFcMember,
    readReferralFromSearch,
    writeFcMember,
} from '@/lib/founding-circle';
import type { FcMember } from '@/lib/founding-circle';
import { maisonUrl } from '@/lib/maison-navigation';

function subscribeClientOnly(callback: () => void): () => void {
    void callback;

    return () => {};
}

function getIsClientSnapshot(): boolean {
    return true;
}

function getIsServerSnapshot(): boolean {
    return false;
}

function getStoredMemberSnapshot(): FcMember | null {
    return readFcMember();
}

function getReferralSnapshot(): number | null {
    return readReferralFromSearch(window.location.search);
}

/**
 * Client-only Founding Circle member portal. Mirrors the prototype's
 * localStorage session and `?ref=` invite banner until a real member API exists.
 */
export function CirclePortal() {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const isClient = useSyncExternalStore(
        subscribeClientOnly,
        getIsClientSnapshot,
        getIsServerSnapshot,
    );
    const storedMember = useSyncExternalStore(
        subscribeClientOnly,
        getStoredMemberSnapshot,
        () => null,
    );
    const referral = useSyncExternalStore(
        subscribeClientOnly,
        getReferralSnapshot,
        () => null,
    );
    const [memberOverride, setMemberOverride] = useState<
        FcMember | null | undefined
    >(undefined);
    const member =
        memberOverride !== undefined ? memberOverride : storedMember;
    const [num, setNum] = useState('');
    const [email, setEmail] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [copyNote, setCopyNote] = useState<string | null>(null);

    const referralUrl = useMemo(() => {
        if (!member) {
            return '';
        }

        if (typeof window === 'undefined') {
            return '';
        }

        return `${window.location.origin}${maisonUrl('circle', locale)}?ref=${member.num}`;
    }, [member, locale]);

    function onLogin(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setError(null);

        const trimmedNum = num.trim();
        const trimmedEmail = email.trim();

        if (!trimmedNum || !trimmedEmail || !trimmedEmail.includes('@')) {
            setError(t('Vul uw editienummer en e-mailadres in.'));

            return;
        }

        const parsed = Number.parseInt(trimmedNum, 10);

        if (!Number.isFinite(parsed) || parsed < 1 || parsed > 100) {
            setError(t('Editienummer moet tussen 1 en 100 liggen.'));

            return;
        }

        const next = { num: parsed, email: trimmedEmail };
        writeFcMember(next);
        setMemberOverride(next);
        setCopyNote(null);
    }

    function onLogout() {
        clearFcMember();
        setMemberOverride(null);
        setNum('');
        setEmail('');
        setError(null);
        setCopyNote(null);
    }

    async function onCopy() {
        if (!referralUrl) {
            return;
        }

        try {
            await navigator.clipboard.writeText(referralUrl);
        } catch {
            // Fallback for older browsers / denied clipboard.
            const input = document.getElementById(
                'fc-ref-input',
            ) as HTMLInputElement | null;
            input?.select();

            try {
                document.execCommand('copy');
            } catch {
                return;
            }
        }

        setCopyNote(t('Link gekopieerd — deel hem met een vriend.'));
    }

    if (!isClient) {
        return (
            <div
                className="mx-auto max-w-190 bg-choc2 px-8 py-16 text-cream md:px-16"
                aria-hidden="true"
            >
                <div className="mx-auto h-40 max-w-110 animate-pulse bg-white/5" />
            </div>
        );
    }

    return (
        <Reveal className="mx-auto max-w-190 bg-choc2 px-8 py-14 text-cream md:px-16 md:py-18">
            <Eyebrow className="text-center text-gold">
                {t('Ledenportaal · Prototype')}
            </Eyebrow>
            <GoldRule center className="mx-auto" />
            <h2 className="mt-2 mb-2.5 text-center font-serif text-[clamp(28px,3.5vw,42px)] font-medium text-cream [&_em]:text-gold [&_em]:italic">
                {t('Uw')} <em>Founding Circle</em> {t('ruimte')}
            </h2>
            <p className="mx-auto mb-7.5 max-w-140 text-center font-sans text-[15px] leading-[1.8] text-sand">
                {t(
                    'Log in met uw editienummer en e-mailadres om uw bestelstatus, exclusieve Journal-inhoud en evenementen te zien.',
                )}
            </p>

            {member ? (
                <div>
                    <div className="mb-7 flex flex-wrap items-center justify-between gap-4 border-b border-gold/20 pb-5.5">
                        <div>
                            <span className="font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                                {t('Welkom, Founding Member')}
                            </span>
                            <div className="mt-1 font-serif text-xl text-cream">
                                {member.email}
                            </div>
                        </div>
                        <div className="text-center">
                            <span className="block font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
                                {t('Uw nummer')}
                            </span>
                            <span className="font-serif text-[28px] text-gold">
                                № {formatEditionNumber(member.num)}
                            </span>
                        </div>
                        <MaisonButton
                            variant="outlineCream"
                            onClick={onLogout}
                            className="px-4 py-2.5"
                        >
                            {t('Afmelden')}
                        </MaisonButton>
                    </div>

                    <div className="mb-6 grid gap-4.5 md:grid-cols-3">
                        <DashCard
                            tag={t('Bestelstatus')}
                            title={t('Gereserveerd')}
                        >
                            <p>
                                {t(
                                    'Uw editienummer is vastgelegd. De Founding Edition wordt in één beperkte productieronde vervaardigd — levering Q1 2027.',
                                )}
                            </p>
                            <div className="mt-3 flex items-center gap-2 font-sans text-[11px] text-cream">
                                <span
                                    aria-hidden="true"
                                    className="inline-block size-1.75 rounded-full bg-gold"
                                />
                                {t('In productiewachtrij')}
                            </div>
                        </DashCard>

                        <DashCard
                            tag={t('Exclusieve Journal')}
                            title={t('Voor leden')}
                        >
                            <p>
                                {t(
                                    'Diepere verhalen, alleen zichtbaar voor de Founding Circle.',
                                )}
                            </p>
                            <div className="mt-3">
                                <LockedRow
                                    label={t('Ontwerpnotities Heritage No.001')}
                                    badge={t('Alleen leden')}
                                />
                                <LockedRow
                                    label={t('Een brief van de oprichter')}
                                    badge={t('Alleen leden')}
                                />
                            </div>
                        </DashCard>

                        <DashCard tag={t('Evenementen')} title={t('Aanstaand')}>
                            <p>{t('Persoonlijke uitnodigingen voor leden.')}</p>
                            <div className="mt-3 font-sans text-xs text-sand">
                                <div className="border-t border-gold/12 py-2.25">
                                    <span className="mr-2 text-gold">
                                        Q4 2026
                                    </span>
                                    {t('Founding Circleontvangst — Antwerpen')}
                                </div>
                                <div className="border-t border-gold/12 py-2.25">
                                    <span className="mr-2 text-gold">
                                        Q1 2027
                                    </span>
                                    {t('Eerste levermoment & padelclinic')}
                                </div>
                            </div>
                        </DashCard>
                    </div>

                    <div className="rounded-lg border border-gold/18 bg-black/20 p-6">
                        <div className="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                            <span className="font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                                {t('Nodig een vriend')}
                            </span>
                            <h3 className="font-serif text-xl font-normal text-cream">
                                {t('Breng leden')}
                            </h3>
                        </div>
                        <p className="mb-3.5 font-sans text-[13px] leading-[1.7] text-sand">
                            {t(
                                'Deel uw persoonlijke link. Vrienden die reserveren via uw link worden aan uw Founding Circle-rekening gekoppeld.',
                            )}
                        </p>
                        <div className="flex flex-col gap-2.5 sm:flex-row">
                            <input
                                id="fc-ref-input"
                                type="text"
                                readOnly
                                value={referralUrl}
                                className="min-w-0 flex-1 rounded border border-gold/25 bg-black/25 px-3.25 py-2.75 font-sans text-xs text-cream"
                            />
                            <MaisonButton variant="gold" onClick={onCopy}>
                                {t('Kopiëren')}
                            </MaisonButton>
                        </div>
                        <p className="mt-3 font-sans text-[9px] tracking-[0.14em] text-stone uppercase">
                            {copyNote ??
                                t(
                                    'Uitnodigingen helpen de Founding Circle sluiten — tracking actief bij lancering.',
                                )}
                        </p>
                    </div>
                </div>
            ) : (
                <form onSubmit={onLogin} className="mx-auto max-w-110">
                    {referral !== null && (
                        <p className="mb-3.5 font-sans text-[11px] tracking-[0.1em] text-gold uppercase">
                            {t('Uitgenodigd door Founding Member')} №{' '}
                            {formatEditionNumber(referral)}
                        </p>
                    )}
                    <div className="mb-3.5 flex flex-col gap-2.5 sm:flex-row">
                        <label className="sr-only" htmlFor="fc-num">
                            {t('Editienummer (bijv. 7)')}
                        </label>
                        <input
                            id="fc-num"
                            inputMode="numeric"
                            value={num}
                            onChange={(event) => setNum(event.target.value)}
                            placeholder={t('Editienummer (bijv. 7)')}
                            className="min-w-0 flex-1 border border-gold/30 bg-white/6 px-3.5 py-3.5 font-sans text-sm text-cream outline-none placeholder:text-sand focus:border-gold"
                        />
                        <label className="sr-only" htmlFor="fc-email">
                            {t('E-mailadres')}
                        </label>
                        <input
                            id="fc-email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder={t('E-mailadres')}
                            className="min-w-0 flex-1 border border-gold/30 bg-white/6 px-3.5 py-3.5 font-sans text-sm text-cream outline-none placeholder:text-sand focus:border-gold"
                        />
                    </div>
                    {error && (
                        <p role="alert" className="mb-3 text-sm text-gold">
                            {error}
                        </p>
                    )}
                    <MaisonButton variant="gold" type="submit" block>
                        {t('Toegang tot mijn ruimte')}
                    </MaisonButton>
                    <p className="mt-4 text-center font-sans text-[9px] tracking-[0.14em] text-stone uppercase">
                        {t(
                            'Prototype — bij lancering gekoppeld aan een beveiligd ledensysteem. Gegevens blijven lokaal in uw browser.',
                        )}
                    </p>
                </form>
            )}
        </Reveal>
    );
}

function DashCard({
    tag,
    title,
    children,
}: {
    tag: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="rounded-lg border border-gold/18 bg-black/20 p-6">
            <span className="font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                {tag}
            </span>
            <h3 className="my-1.5 font-serif text-xl font-normal text-cream">
                {title}
            </h3>
            <div className="font-sans text-xs leading-[1.7] text-sand">
                {children}
            </div>
        </div>
    );
}

function LockedRow({ label, badge }: { label: string; badge: string }) {
    return (
        <div className="flex justify-between gap-2 border-t border-gold/12 py-2.25 font-sans text-xs text-sand">
            <span>{label}</span>
            <span className="shrink-0 text-[8px] tracking-[0.16em] text-gold uppercase">
                {badge}
            </span>
        </div>
    );
}
