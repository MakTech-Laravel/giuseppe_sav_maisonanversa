<?php

namespace App\Enums;

enum ProductSectionKey: string
{
    case Specs = 'specs';
    case Includes = 'includes';
    case Guarantees = 'guarantees';
    case Unboxing = 'unboxing';
    case Craft = 'craft';
    case Trust = 'trust';
    case Service = 'service';
    case Faq = 'faq';
    case Related = 'related';

    /**
     * Admin-facing label. Dutch source copy doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Specs => __('Specifications'),
            self::Includes => __('Bundled'),
            self::Guarantees => __('Guarantees'),
            self::Unboxing => __('What is in the box'),
            self::Craft => __('Craftsmanship'),
            self::Trust => __('Trust badges'),
            self::Service => __('Service & security'),
            self::Faq => __('Frequently asked questions'),
            self::Related => __('Next chapters'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Specs => __('Specification table beside the product gallery.'),
            self::Includes => __('List of included items, also shown on the homepage.'),
            self::Guarantees => __('Guarantee block below the order buttons.'),
            self::Unboxing => __('Numbered grid with the full unboxing experience.'),
            self::Craft => __('Materials block with image and description.'),
            self::Trust => __('Compact strip with trust badges.'),
            self::Service => __('Cards with shipping, returns and reservation policy.'),
            self::Faq => __('Frequently asked questions for this product.'),
            self::Related => __('Related products at the bottom of the page.'),
        };
    }

    /**
     * Sections rendered inside the product detail block have no heading of their own.
     */
    public function usesHeading(): bool
    {
        return ! in_array($this, [self::Specs, self::Includes, self::Guarantees, self::Trust], true);
    }

    public function usesImage(): bool
    {
        return $this === self::Craft;
    }

    /**
     * FAQ items live in `product_faqs`; related products are resolved by query.
     */
    public function usesItems(): bool
    {
        return ! in_array($this, [self::Faq, self::Related], true);
    }

    /**
     * Item columns that are meaningful for this section.
     *
     * @return list<string>
     */
    public function itemFields(): array
    {
        return match ($this) {
            self::Specs => ['title', 'body'],
            self::Includes => ['title'],
            self::Guarantees, self::Trust => ['icon', 'title'],
            self::Craft, self::Unboxing => ['number_label', 'title', 'body'],
            self::Service => ['icon', 'title', 'body'],
            self::Faq, self::Related => [],
        };
    }

    public function defaultSortOrder(): int
    {
        return match ($this) {
            self::Specs => 0,
            self::Includes => 1,
            self::Guarantees => 2,
            self::Unboxing => 3,
            self::Craft => 4,
            self::Trust => 5,
            self::Service => 6,
            self::Faq => 7,
            self::Related => 8,
        };
    }

    /**
     * Catalogue shared with the admin form so every product exposes every section.
     *
     * @return list<array{key: string, label: string, description: string, uses_heading: bool, uses_image: bool, uses_items: bool, item_fields: list<string>, sort_order: int}>
     */
    public static function catalogue(): array
    {
        return array_map(fn (self $case): array => [
            'key' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'uses_heading' => $case->usesHeading(),
            'uses_image' => $case->usesImage(),
            'uses_items' => $case->usesItems(),
            'item_fields' => $case->itemFields(),
            'sort_order' => $case->defaultSortOrder(),
        ], self::cases());
    }
}
