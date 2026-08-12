import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';

type FeedComposeProps = {
    initials: string;
    onPublish: (text: string) => void;
};

export function FeedCompose({ initials, onPublish }: FeedComposeProps) {
    const { t } = useTranslation();
    const [text, setText] = useState('');

    function handlePublish() {
        const trimmed = text.trim();

        if (!trimmed) {
            return;
        }

        onPublish(trimmed);
        setText('');
    }

    return (
        <div className="mb-6 border border-gold/20 bg-cream2 p-6">
            <div className="mb-3.5 flex items-center gap-3.5">
                <Monogram initials={initials} emphasis />
                <textarea
                    value={text}
                    onChange={(event) => setText(event.target.value)}
                    placeholder={t(
                        'Deel uw ervaring met Heritage No.001, een sessie, een gedachte...',
                    )}
                    className="h-20 flex-1 resize-none border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc transition-colors outline-none focus:border-gold2"
                />
            </div>
            <div className="flex items-center justify-between">
                <span className="font-sans text-[9px] tracking-[0.15em] text-stone">
                    {t(
                        'Alleen zichtbaar voor Founding Members & Club Corner leden',
                    )}
                </span>
                <button
                    type="button"
                    onClick={handlePublish}
                    className="cursor-pointer bg-choc px-6 py-3 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                >
                    {t('Publiceer')}
                </button>
            </div>
        </div>
    );
}
