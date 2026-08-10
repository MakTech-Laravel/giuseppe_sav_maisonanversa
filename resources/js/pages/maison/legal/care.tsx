import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    LegalHeading,
    LegalList,
    LegalListItem,
    LegalPageLayout,
    LegalParagraph,
} from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';

export default function Care() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Zorg & Garantie')} />

            <LegalPageLayout
                titleKey="Zorg & Garantie"
                introKey="Een Heritage-racket is gebouwd om te duren. Met de juiste zorg blijft hij generaties meegaan."
                noteKey="Voorgenomen servicebeleid — definitief bevestigd vóór lancering"
            >
                <LegalHeading>{t('Onderhoud')}</LegalHeading>
                <LegalList>
                    <LegalListItem>
                        <strong>{t('Leder:')}</strong>{' '}
                        {t(
                            'behandel het leder tweemaal per jaar met een neutrale lederbalsem. Vermijd langdurig vocht en direct zonlicht.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        <strong>{t('Koolstof:')}</strong>{' '}
                        {t(
                            'reinig met een zachte, droge doek. Geen bijtende middelen.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        <strong>{t('Bewaring:')}</strong>{' '}
                        {t(
                            'berg de racket op in de omslag, op een droge plaats, bij gematigde temperatuur.',
                        )}
                    </LegalListItem>
                </LegalList>

                <LegalHeading>{t('Garantie')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Wij bieden garantie op materiaal- en fabricagefouten, geldig vanaf de leveringsdatum. De garantie dekt geen slijtage door normaal gebruik, onjuist onderhoud, of beschadiging door vallen of stoten.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('Maison Care-service')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Bij twijfel of schade stuurt u de racket terug naar ons atelier in Antwerpen voor inspectie. Wij beoordelen kosteloos en adviseren u persoonlijk over herstelling of vervanging. Een eventuele servicekostenopgave ontvangt u vóór aanvang van de werkzaamheden.',
                    )}
                </LegalParagraph>
                <LegalParagraph>
                    {t('Registreer uw editienummer via onze')}{' '}
                    <MaisonLink
                        to="contact"
                        className="text-gold2 underline underline-offset-2 hover:text-gold"
                    >
                        {t('contactpagina')}
                    </MaisonLink>{' '}
                    {t('om uw garantieperiode te activeren.')}
                </LegalParagraph>
            </LegalPageLayout>
        </>
    );
}
