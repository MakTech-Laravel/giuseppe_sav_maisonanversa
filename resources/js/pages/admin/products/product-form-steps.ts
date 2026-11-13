import type { FormStep } from '@/components/admin/form-stepper';

/** Shared step rail for product create and edit screens. */
export const PRODUCT_FORM_STEPS: FormStep[] = [
    { id: 'basics', label: 'Basics', description: 'Name, slug and hero copy' },
    { id: 'pricing', label: 'Price', description: 'Price, edition and stock' },
    { id: 'media', label: 'Media', description: 'Cover and gallery' },
    { id: 'sections', label: 'Sections', description: 'Page content' },
    { id: 'faq', label: 'FAQ', description: 'Questions for this product' },
    {
        id: 'publish',
        label: 'Publish',
        description: 'Review and publish',
    },
];
