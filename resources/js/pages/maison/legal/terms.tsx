import { useTranslation } from 'react-i18next';
import {
    LegalHeading,
    LegalPageLayout,
    LegalParagraph,
} from '@/components/maison/legal/legal-page-layout';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Terms() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="terms" />

            <LegalPageLayout
                titleKey="Algemene voorwaarden"
                introKey="Deze voorwaarden zijn van toepassing op aankopen via maisonanversa.com. Door te bestellen aanvaardt u deze voorwaarden."
                noteKey="Concept ter inzage — definitieve voorwaarden worden bevestigd vóór lancering"
            >
                <LegalHeading>{t('1. Definities')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        '"Maison Anversa", "wij": de uitgever van deze site. "U": de klant. "Product": het door u bestelde racket en toebehoren.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('2. Aanbod en prijs')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Alle producten worden aangeboden zolang de voorraad strekt. Prijzen zijn in euro, inclusief btw, exclusief eventuele heffingen of verzending buiten Europa. Wij streven ernaar productinformatie juist te houden; bij evidente fouten behouden wij ons recht voor om de overeenkomst te herroepen.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('3. Bestelling en bevestiging')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Een bestelling is een aanbod van uw kant. De overeenkomst komt tot stand op het moment van onze bevestiging. Voor de Founding Edition bevestigen wij uw editienummer persoonlijk.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('4. Betaling')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Betaling verloopt via onze beveiligde betaaldienst. Zolang uw betaling niet is voltooid, is de bestelling niet definitief.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('5. Levering')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'De Heritage No.001 Founding Edition levert in Q1 2027, in volgorde van reservering. Levertijden zijn indicatief; vertragingen melden wij tijdig.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('6. Eigendom en risico')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Het risico gaat op u over bij levering. Het eigendom gaat over bij volledige betaling.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('7. Klachten en garantie')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Ontvangt u een beschadigd of fout product, meld dit dan binnen 14 dagen. Zie ons',
                    )}{' '}
                    <MaisonLink
                        to="care"
                        className="text-gold2 underline underline-offset-2 hover:text-gold"
                    >
                        {t('zorg- en garantiebeleid')}
                    </MaisonLink>{' '}
                    {t('en')}{' '}
                    <MaisonLink
                        to="shipping"
                        className="text-gold2 underline underline-offset-2 hover:text-gold"
                    >
                        {t('verzend- en retourbeleid')}
                    </MaisonLink>
                    .
                </LegalParagraph>

                <LegalHeading>{t('8. Aansprakelijkheid')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Onze aansprakelijkheid is beperkt tot het bedrag van uw bestelling, behalve bij opzet of grove nalatigheid.',
                    )}
                </LegalParagraph>

                <LegalHeading>{t('9. Toepasselijk recht')}</LegalHeading>
                <LegalParagraph>
                    {t(
                        'Op deze voorwaarden is Belgisch recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter te Antwerpen.',
                    )}
                </LegalParagraph>
            </LegalPageLayout>
        </>
    );
}
