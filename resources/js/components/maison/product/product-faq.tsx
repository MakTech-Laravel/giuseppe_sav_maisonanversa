import { useTranslation } from 'react-i18next';
import { MaisonAccordion } from '@/components/maison/ui/maison-accordion';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

const FAQ = [
    {
        value: 'delivery',
        question: 'Wanneer wordt mijn racket geleverd?',
        answer: 'De Founding Edition wordt in één beperkte productieronde van 100 stuks vervaardigd. Bestellingen worden geleverd na definitieve kwaliteitscontrole en goedkeuring van de productie. Verwachte levering is Q1 2027. U ontvangt tussentijds updates over de voortgang.',
    },
    {
        value: 'unique',
        question: 'Hoe weet ik dat mijn nummer uniek is?',
        answer: 'Elk racket is individueel gestempeld (001–100) en vergezeld van een Heritage Certificaat met hetzelfde nummer en het oprichterzegel. Het nummer staat ook in ons register.',
    },
    {
        value: 'choose',
        question: 'Kan ik mijn nummer kiezen?',
        answer: 'Binnen de beschikbare nummers kunt u een voorkeur opgeven bij reservering. Leden van de Founding Circle hebben voorrang op lagere nummers.',
    },
    {
        value: 'repeat',
        question: 'Wordt Heritage No.001 opnieuw gemaakt?',
        answer: 'Nee. De Founding Edition wordt niet herhaald — 100 stuks, eenmalig. Heritage No.001 kan daarna als reguliere collectie beschikbaar blijven. Toekomstige releases dragen andere nummers (No.002, No.003).',
    },
] as const;

export function ProductFaq() {
    const { t } = useTranslation();

    return (
        <Section tone="cream">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    <Eyebrow tone="gold2">{t('Vragen')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] uppercase">
                        {t('Veelgestelde vragen.')}
                    </h2>
                </Reveal>

                <MaisonAccordion
                    className="mx-auto max-w-205"
                    entries={FAQ.map((item) => ({
                        value: item.value,
                        question: t(item.question),
                        answer: t(item.answer),
                    }))}
                />
            </Wrap>
        </Section>
    );
}
