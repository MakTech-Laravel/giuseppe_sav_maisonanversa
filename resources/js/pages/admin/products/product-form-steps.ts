import type { FormStep } from '@/components/admin/form-stepper';

/** Shared step rail for product create and edit screens. */
export const PRODUCT_FORM_STEPS: FormStep[] = [
    { id: 'basics', label: 'Basis', description: 'Naam, slug en herotekst' },
    { id: 'pricing', label: 'Prijs', description: 'Prijs, editie en voorraad' },
    { id: 'media', label: 'Beeld', description: 'Cover en galerij' },
    { id: 'sections', label: 'Secties', description: 'Inhoud van de pagina' },
    { id: 'faq', label: 'FAQ', description: 'Vragen bij dit product' },
    {
        id: 'publish',
        label: 'Publiceren',
        description: 'Controleer en publiceer',
    },
];
