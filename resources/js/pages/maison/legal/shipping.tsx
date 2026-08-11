import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import {
    LegalHeading,
    LegalList,
    LegalListItem,
    LegalPageLayout,
    LegalParagraph,
} from '@/components/maison/legal/legal-page-layout';

export default function Shipping() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="shipping" />

            <LegalPageLayout
                titleKey="Verzending & Retour"
                introKey="Elke Heritage-racket wordt met de hand verpakt en met zorg verzonden. Hieronder onze voorgenomen service."
                noteKey="Voorgenomen servicebeleid — definitief bevestigd vóór lancering"
            >
                <LegalHeading>{t('Verzending')}</LegalHeading>
                <LegalList>
                    <LegalListItem>
                        <strong>{t('Europa:')}</strong>{' '}
                        {t(
                            'verzending inbegrepen. Geleverd via geadresseerde, verzekerde zending.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        <strong>{t('Buiten Europa:')}</strong>{' '}
                        {t(
                            'op aanvraag — wij bevestigen tarief en levertijd persoonlijk.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        <strong>{t('Verpakking:')}</strong>{' '}
                        {t(
                            'handgemaakte omslag, certificaat van echtheid en genummerd editienummer.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        <strong>{t('Levertijd Founding Edition:')}</strong>{' '}
                        {t('Q1 2027, in volgorde van reservering.')}
                    </LegalListItem>
                </LegalList>

                <LegalHeading>{t('Retourneren')}</LegalHeading>
                <LegalList>
                    <LegalListItem>
                        {t(
                            'U heeft een herroepingsrecht van 14 dagen na levering, conform de Belgische consumentenwetgeving.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Het product dient ongebruikt, in originele verpakking en met certificaat te worden geretourneerd.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Genummerde editiestukken kunnen, indien voorzien van persoonlijke gravering, niet worden geretourneerd tenzij er een gebrek is.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Retourzendingen dienen vooraf te worden aangemeld via onze contactpagina.',
                        )}
                    </LegalListItem>
                </LegalList>

                <LegalHeading>{t('Terugbetaling')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Na ontvangst en controle van de retour betalen wij het aankoopbedrag terug binnen 14 dagen, op het oorspronkelijke betaalmiddel. Retourkosten zijn voor u, tenzij het product niet aan de overeenkomst beantwoordt.',
                    )}
                </LegalParagraph>
            </LegalPageLayout>
        </>
    );
}
