import { Node, mergeAttributes } from '@tiptap/core';

export const LegalSummary = Node.create({
    name: 'legalSummary',
    content: 'inline*',
    parseHTML() {
        return [{ tag: 'summary' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['summary', mergeAttributes(HTMLAttributes), 0];
    },
});

export const LegalDetails = Node.create({
    name: 'legalDetails',
    group: 'block',
    content: 'legalSummary block+',
    defining: true,
    addAttributes() {
        return {
            open: {
                default: true,
                parseHTML: (element) => element.hasAttribute('open'),
                renderHTML: (attributes) =>
                    attributes.open ? { open: 'open' } : {},
            },
        };
    },
    parseHTML() {
        return [{ tag: 'details' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['details', mergeAttributes(HTMLAttributes), 0];
    },
});
