export function legalPageTitleKey(slug: string): string {
    switch (slug) {
        case 'privacy':
            return 'Privacy Policy';
        case 'terms':
            return 'Terms & Conditions';
        case 'withdrawal':
            return 'Model withdrawal form';
        case 'shipping':
            return 'Shipping & Returns';
        case 'care':
            return 'Care & Warranty';
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
