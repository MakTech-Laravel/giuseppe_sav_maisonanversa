import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonModal, modalInputClassName } from '@/components/maison/modals/maison-modal';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
type CertificateModalProps = {
    onClose: () => void;
};

function formatEditionNumber(value: string): string {
    const trimmed = value.trim();

    if (!trimmed) {
        return '№ 0__';
    }

    const parsed = Number.parseInt(trimmed, 10);

    if (Number.isNaN(parsed)) {
        return '№ 0__';
    }

    return `№ ${String(parsed).padStart(3, '0')}`;
}

/**
 * A live-preview certificate of authenticity. The three fields below update
 * the holder, edition number and monogram in place — the prototype wired the
 * same inputs from the order flow; here they stand alone for the preview link.
 */
export function CertificateModal({ onClose }: CertificateModalProps) {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [number, setNumber] = useState('');
    const [monogram, setMonogram] = useState('');

    const owner = useMemo(
        () => name.trim() || t('Naam van de houder'),
        [name, t],
    );
    const edition = useMemo(() => formatEditionNumber(number), [number]);
    const engraved = useMemo(
        () => monogram.trim().toUpperCase() || '—',
        [monogram],
    );

    return (
        <MaisonModal
            label="Certificaat van Echtheid"
            onClose={onClose}
            panelClassName="max-w-[560px] p-0"
        >
            <div className="relative bg-[#f6f1e8] px-8 py-11 text-center ma-sm:px-10">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-2 rounded-sm border border-gold/30"
                />

                <span className="font-sans text-[9px] tracking-[0.3em] text-gold uppercase">
                    {t('Maison Anversa · Antwerpen · MMXXVI')}
                </span>
                <h2 className="mt-2 font-serif text-[30px] font-medium text-choc ma-sm:text-[25px]">
                    {t('Certificaat van Echtheid')}
                </h2>
                <div
                    aria-hidden="true"
                    className="mx-auto my-3.5 h-px w-10 bg-gold/50"
                />

                <p className="mx-auto max-w-[380px] font-sans text-xs leading-[1.7] text-choc3">
                    {t('Hiermee wordt verklaard dat het hieronder beschreven werk')}
                </p>
                <p className="my-3.5 font-serif text-lg text-choc italic">
                    {t('Heritage No.001 — Founding Edition')}
                </p>
                <p className="mx-auto max-w-[380px] font-sans text-xs leading-[1.7] text-choc3">
                    {t(
                        'een origineel, met de hand afgewerkt erfgoedstuk is, individueel genummerd',
                    )}
                </p>

                <div className="my-4.5 flex justify-center gap-8 ma-sm:gap-12">
                    <div className="flex flex-col gap-1">
                        <span className="font-sans text-[8px] tracking-[0.25em] text-choc3 uppercase">
                            {t('Editienummer')}
                        </span>
                        <span className="font-serif text-[26px] text-gold">
                            {edition}
                        </span>
                    </div>
                    <div className="flex flex-col gap-1">
                        <span className="font-sans text-[8px] tracking-[0.25em] text-choc3 uppercase">
                            {t('Van')}
                        </span>
                        <span className="font-serif text-[26px] text-gold">
                            100
                        </span>
                    </div>
                </div>

                <p className="font-sans text-xs text-choc3">{t('Houder')}</p>
                <p className="inline-block min-w-[200px] border-b border-gold/35 px-6 pb-1 font-serif text-xl text-choc">
                    {owner}
                </p>

                <p className="mt-1.5 font-sans text-xs text-choc3">
                    {t('Gegraveerd in het leder')}
                </p>
                <p className="inline-block min-w-[120px] border-b border-gold/30 px-6 pb-1 font-serif text-2xl tracking-[0.15em] text-choc uppercase">
                    {engraved}
                </p>

                <div className="mt-6 flex items-center justify-between px-2">
                    <div className="text-left">
                        <span className="block font-serif text-xl text-choc italic">
                            {t('Yusuf Savran')}
                        </span>
                        <span className="mt-1 block font-sans text-[9px] tracking-[0.18em] text-choc3 uppercase">
                            {t('Oprichter · Antwerpen')}
                        </span>
                    </div>

                    <div className="relative size-[66px] text-gold">
                        <svg
                            viewBox="0 0 80 80"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.1"
                            aria-hidden="true"
                            className="absolute inset-0 h-full w-full"
                        >
                            <circle cx="40" cy="40" r="37" />
                            <circle
                                cx="40"
                                cy="40"
                                r="30"
                                strokeDasharray="2 3"
                            />
                            <path
                                d="M40 13 L42 18 L47 18 L43 21 L45 26 L40 23 L35 26 L37 21 L33 18 L38 18 Z"
                                fill="currentColor"
                                stroke="none"
                            />
                        </svg>
                        <PlaceholderImage
                            asset="logo-emblem"
                            ratio={null}
                            alt=""
                            captioned={false}
                            className="absolute inset-[25%] overflow-hidden rounded-sm"
                        />
                    </div>
                </div>

                <p className="mt-4.5 font-sans text-[9px] tracking-[0.16em] text-choc3 uppercase opacity-80">
                    {t('Voorbeeld — definitief certificaat bij levering')}
                </p>
            </div>

            <div className="flex flex-wrap gap-2.5 border-t border-gold/22 bg-[#f6f1e8] px-8 py-7 ma-sm:px-10">
                <input
                    type="text"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder={t('Uw naam')}
                    className={cnInput()}
                />
                <input
                    type="text"
                    inputMode="numeric"
                    value={number}
                    onChange={(event) => setNumber(event.target.value)}
                    placeholder={t('Editienummer (bijv. 7)')}
                    className={cnInput()}
                />
                <input
                    type="text"
                    maxLength={3}
                    value={monogram}
                    onChange={(event) =>
                        setMonogram(event.target.value.toUpperCase())
                    }
                    placeholder={t('Monogram (3 letters)')}
                    className={cnInput()}
                />
            </div>
        </MaisonModal>
    );
}

function cnInput(): string {
    return `${modalInputClassName} min-w-[120px] flex-1 rounded-sm bg-[#fffdf8] font-sans text-[13px]`;
}
