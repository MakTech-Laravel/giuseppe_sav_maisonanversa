import { useTranslation } from 'react-i18next';

const TRUST = [
    { icon: '◈', text: 'Genummerd 001–100' },
    { icon: '✓', text: 'Echtheids-certificaat' },
    { icon: '◆', text: 'Ontworpen in Antwerpen' },
    { icon: '★', text: 'Founding Circle lid' },
] as const;

export function ProductTrust() {
    const { t } = useTranslation();

    return (
        <div className="bg-choc">
            <div className="flex flex-wrap justify-center gap-x-14 gap-y-8 px-8 py-13">
                {TRUST.map((item) => (
                    <div key={item.text} className="text-center">
                        <div
                            aria-hidden="true"
                            className="mb-2.5 text-[26px] text-gold"
                        >
                            {item.icon}
                        </div>
                        <div className="font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
                            {t(item.text)}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
