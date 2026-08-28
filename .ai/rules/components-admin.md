---
paths:
  - resources/js/components/admin/product-section-editor.tsx
---

# Components Admin

## Product section Lucide icons
Guarantee/trust/service item icons use LucideIconPicker (dialog mode) and store Lucide kebab keys in product_section_items.icon. Craft section imagery uses FileUpload into product_sections.image_path (not image_key). Storefront renders via SectionIcon with Lucide fallback to legacy Unicode glyphs.

## Product section Lucide icons and craft upload
Guarantee/trust/service item icons use LucideIconPicker (dialog) and store Lucide kebab keys in product_section_items.icon. Craft imagery uses FileUpload into product_sections.image_path (legacy image_key remains read-only fallback). Storefront uses SectionIcon: Lucide when valid, else Unicode glyph.
