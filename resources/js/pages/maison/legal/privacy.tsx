import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import {
    LegalHeading,
    LegalList,
    LegalListItem,
    LegalPageLayout,
    LegalParagraph,
} from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';

export default function Privacy() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="privacy" />

            <LegalPageLayout
                titleKey="Privacybeleid"
                introKey="Maison Anversa hecht waarde aan uw vertrouwen. Deze pagina legt uit welke persoonsgegevens wij verwerken, waarom, en welke rechten u heeft."
                noteKey="Concept ter inzage — definitief beleid wordt bevestigd vóór lancering"
            >
                <LegalHeading>{t('1. Wie zijn wij')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Verwerkingsverantwoordelijke: Maison Anversa, gevestigd te Antwerpen, België (bedrijfsgegevens en btw-nummer worden bevestigd vóór lancering).',
                    )}{' '}
                    {t('Bereikbaar via')}{' '}
                    <MaisonLink
                        to="contact"
                        className="text-gold2 underline underline-offset-2 hover:text-gold"
                    >
                        {t('onze contactpagina')}
                    </MaisonLink>
                    .
                </LegalParagraph>

                <LegalHeading>{t('2. Welke gegevens wij verwerken')}</LegalHeading>
                <LegalList>
                    <LegalListItem>
                        {t(
                            'Contact- en bestelgegevens: naam, e-mailadres, telefoonnummer, postadres.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Betalingsgegevens: verwerkt door onze betaaldienst (niet door ons opgeslagen).',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Nieuwsbrief-aanmeldingen: e-mailadres en voorkeuren.',
                        )}
                    </LegalListItem>
                    <LegalListItem>
                        {t(
                            'Technische gegevens: IP-adres en browsergegevens, via cookies.',
                        )}
                    </LegalListItem>
                </LegalList>

                <LegalHeading>{t('3. Doeleinden en rechtsgronden')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Wij verwerken uw gegevens om bestellingen te verwerken, uw reservering te bevestigen, klantenservice te bieden, en — met uw toestemming — onze Heritage Letter te sturen. Rechtsgronden: uitvoering van een overeenkomst, gerechtvaardigd belang, en uw expliciete toestemming.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('4. Bewaartermijnen')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Wij bewaren gegevens niet langer dan nodig voor het doel waarvoor ze zijn verzameld, of zover wettelijk verplicht. Nieuwsbrief-gegevens worden bewaard tot u zich uitschrijft.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('5. Delen met derden')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Wij delen uw gegevens uitsluitend met verwerkers die nodig zijn voor onze dienstverlening (betaaldienst, vervoerder, e-mailprovider), onder geheimhouding en enkel voor hun taak. Wij verkopen uw gegevens nooit.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('6. Uw rechten')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'U heeft recht op inzage, correctie, verwijdering, beperking van verwerking, overdraagbaarheid, en bezwaar. U kunt zich te allen tijde uitschrijven voor de nieuwsbrief. Stuur een verzoek via onze contactpagina; wij reageren binnen dertig dagen.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('7. Cookies')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Onze site gebruikt minimale cookies voor functionaliteit en anonimiserende analyse. U kunt cookies beheren via uw browserinstellingen.',
                    )}
                </LegalParagraph>
            </LegalPageLayout>
        </>
    );
}
