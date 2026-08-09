import { useTranslation } from 'react-i18next';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';

/**
 * NL / EN / FR. Switching is an Inertia visit to the same page under the other
 * prefix, so the URL stays shareable — the prototype rewrote text nodes in place
 * and reloaded, which left the address bar saying nothing about the language.
 */
export function LanguageSwitcher({ className }: { className?: string }) {
    const { locale, availableLocales, switchLocale } = useLocale();
    const { t } = useTranslation();

    return (
        <div
            className={cn('flex items-center gap-1.5', className)}
            role="group"
            aria-label={t('Taal')}
        >
            {availableLocales.map((option) => (
                <button
                    key={option}
                    type="button"
                    lang={option}
                    aria-current={option === locale ? 'true' : undefined}
                    onClick={() => void switchLocale(option)}
                    className={cn(
                        'rounded-sm border px-2 py-1.5 font-sans text-[10px] font-semibold tracking-[0.08em] uppercase transition-colors',
                        option === locale
                            ? 'border-gold bg-gold/12 text-cream'
                            : 'border-transparent text-sand hover:border-gold/40 hover:text-cream',
                    )}
                >
                    {option}
                </button>
            ))}
        </div>
    );
}
