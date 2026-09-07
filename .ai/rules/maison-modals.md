---
paths:
  - 'resources/js/components/maison/modals/**'
  - 'resources/js/components/ui/select.tsx'
  - 'resources/js/components/gender-select.tsx'
---

# Maison Modals & Selects

## Select dropdowns must stack above MaisonModal
`MaisonModal` uses `z-9990`. Radix/shadcn `SelectContent` portals to `document.body`, so it must use a higher z-index (`z-[10050]`) or the list renders under the overlay and cannot be clicked — this broke gender on register. `GenderSelect` sets `modal={false}` so it does not fight the custom modal focus trap. Do not lower Select below the modal stack.
