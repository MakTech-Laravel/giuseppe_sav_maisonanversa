export function legalPageTitleKey(slug: string): string {
    switch (slug) {
        case 'privacy':
            return 'Privacybeleid';
        case 'terms':
            return 'Algemene voorwaarden';
        case 'shipping':
            return 'Verzending & Retour';
        case 'care':
            return 'Zorg & Garantie';
        default:
            return slug;
    }
}

export function legalPageTitle(
    slug: string,
    t: (key: string) => string,
): string {
    return t(legalPageTitleKey(slug));
}
