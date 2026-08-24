import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Reveal } from '@/components/maison/ui/reveal';
import { useLocale } from '@/hooks/use-locale';
import { maisonUrl } from '@/lib/maison-navigation';
import type { JournalCard as JournalCardData } from '@/types/journal';

export function JournalCard({ entry }: { entry: JournalCardData }) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const href = `${maisonUrl('journal', locale)}/${entry.slug}`;

    return (
        <Reveal className="group">
            <MaisonLink href={href} className="block">
                <div className="relative mb-4 aspect-4/3 overflow-hidden bg-choc2">
                    {entry.image_url ? (
                        <img
                            src={entry.image_url}
                            alt={entry.title}
                            className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                        />
                    ) : (
                        <PlaceholderImage
                            asset={entry.asset}
                            ratio={null}
                            alt={entry.title}
                            captioned={false}
                            overlay="linear-gradient(to top, rgba(41,28,24,0.5), rgba(41,28,24,0.1))"
                            className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
                        />
                    )}
                </div>
                <span className="mb-2 block font-sans text-[9px] tracking-[0.22em] text-gold2 uppercase">
                    {entry.category}
                </span>
                <h2 className="mb-2 font-serif text-[22px] leading-tight font-medium text-choc transition-colors group-hover:text-gold2">
                    {entry.title}
                </h2>
                <p className="mb-3 text-[14px] leading-[1.7] text-choc3">
                    {entry.excerpt}
                </p>
                <p className="mb-3 font-sans text-[10px] tracking-[0.12em] text-stone uppercase">
                    {entry.meta}
                </p>
                <span className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                    {t('Lees verder')} →
                </span>
            </MaisonLink>
        </Reveal>
    );
}
