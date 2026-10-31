import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

function greetingKey(hour: number): string {
    if (hour < 5) {
        return 'Goedenacht uit Antwerpen';
    }

    if (hour < 12) {
        return 'Goedemorgen uit Antwerpen';
    }

    if (hour < 18) {
        return 'Goedemiddag uit Antwerpen';
    }

    return 'Goedenavond uit Antwerpen';
}

export function OpeningGreeting() {
    const { t } = useTranslation();
    const key = useMemo(() => greetingKey(new Date().getHours()), []);

    return (
        <p className="greeting ma-in ma-t0" id="opening-greeting">
            {t(key)}
        </p>
    );
}
