<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the product content JSON columns and the shared `context = 'product'`
 * FAQ rows into `product_sections`, `product_section_items` and `product_faqs`.
 * Section headings that used to be hardcoded in the React components are
 * seeded here so every product keeps rendering identical copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'specs')) {
            return;
        }

        $now = Carbon::now();
        $globalFaqs = DB::table('faqs')
            ->where('context', 'product')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        DB::table('products')->orderBy('id')->each(function (object $product) use ($now, $globalFaqs): void {
            foreach ($this->sectionDefinitions() as $key => $definition) {
                $existing = DB::table('product_sections')
                    ->where('product_id', $product->id)
                    ->where('key', $key)
                    ->first();

                if ($existing !== null) {
                    continue;
                }

                $sectionId = DB::table('product_sections')->insertGetId([
                    'product_id' => $product->id,
                    'key' => $key,
                    'eyebrow' => $definition['eyebrow'] ?? null,
                    'heading' => $definition['heading'] ?? null,
                    'subheading' => $definition['subheading'] ?? null,
                    'intro' => $definition['intro'] ?? null,
                    'image_path' => null,
                    'image_key' => $definition['image_key'] ?? null,
                    'is_visible' => true,
                    'sort_order' => $definition['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $source = $definition['source'] ?? null;
                $mapper = $definition['map'] ?? null;

                $mapped = $source !== null && $mapper !== null
                    ? array_map($mapper, $this->decodeJsonColumn($product->{$source} ?? null))
                    : ($definition['static_items'] ?? []);

                $items = [];

                foreach (array_values($mapped) as $sort => $item) {

                    if (($item['title'] ?? null) === null && ($item['body'] ?? null) === null) {
                        continue;
                    }

                    $items[] = [
                        'product_section_id' => $sectionId,
                        'number_label' => $item['number_label'] ?? null,
                        'icon' => $item['icon'] ?? null,
                        'title' => $item['title'] ?? null,
                        'body' => $item['body'] ?? null,
                        'image_path' => null,
                        'sort_order' => $sort,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($items !== []) {
                    DB::table('product_section_items')->insert($items);
                }
            }

            $hasFaqs = DB::table('product_faqs')->where('product_id', $product->id)->exists();

            if ($hasFaqs) {
                return;
            }

            $faqRows = $globalFaqs
                ->values()
                ->map(fn (object $faq, int $sort): array => [
                    'product_id' => $product->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sort_order' => $sort,
                    'is_published' => (bool) $faq->is_published,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($faqRows !== []) {
                DB::table('product_faqs')->insert($faqRows);
            }
        });
    }

    public function down(): void
    {
        DB::table('product_section_items')->delete();
        DB::table('product_sections')->delete();
        DB::table('product_faqs')->delete();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sectionDefinitions(): array
    {
        return [
            'specs' => [
                'sort_order' => 0,
                'source' => 'specs',
                'map' => fn (array $row): array => [
                    'title' => $row['label'] ?? null,
                    'body' => $row['value'] ?? null,
                ],
            ],
            'includes' => [
                'sort_order' => 1,
                'source' => 'includes',
                'map' => fn (array $row): array => [
                    'title' => $row['value'] ?? null,
                ],
            ],
            'guarantees' => [
                'sort_order' => 2,
                'source' => 'guarantees',
                'map' => fn (array $row): array => [
                    'icon' => $row['icon'] ?? null,
                    'title' => $row['text'] ?? null,
                ],
            ],
            'unboxing' => [
                'sort_order' => 3,
                'eyebrow' => 'Wat in de Doos Zit',
                'heading' => 'De volledige Heritage Ervaring',
                'intro' => 'Elke Heritage No.001 wordt geleverd als één complete ervaring. De volgorde is intentioneel.',
                'source' => 'unboxing_steps',
                'map' => fn (array $row): array => [
                    'number_label' => $row['num'] ?? null,
                    'title' => $row['title'] ?? null,
                    'body' => $row['desc'] ?? null,
                ],
            ],
            'craft' => [
                'sort_order' => 4,
                'eyebrow' => 'Vakmanschap',
                'heading' => 'Elk detail',
                'subheading' => 'met opzet.',
                'intro' => 'Heritage No.001 wordt gebouwd met materialen die zelden in padel voorkomen. Niet voor de show — voor hoe het voelt in de hand en hoe het veroudert door de jaren.',
                'image_key' => 'atelier-workshop',
                'source' => 'materials',
                'map' => fn (array $row): array => [
                    'number_label' => $row['num'] ?? null,
                    'title' => $row['name'] ?? null,
                    'body' => $row['desc'] ?? null,
                ],
            ],
            'trust' => [
                'sort_order' => 5,
                'source' => 'trust_badges',
                'map' => fn (array $row): array => [
                    'icon' => $row['icon'] ?? null,
                    'title' => $row['text'] ?? null,
                ],
            ],
            'service' => [
                'sort_order' => 6,
                'eyebrow' => 'Service & Veiligheid',
                'heading' => 'Met zorg geleverd.',
                'intro' => 'Voorgenomen servicebeleid — definitief bij lancering.',
                'static_items' => [
                    [
                        'icon' => '◆',
                        'title' => 'Verzending',
                        'body' => 'Gratis verzekerd verzonden binnen de Benelux. EU-levering in 3–5 werkdagen. Elk pakket handmatig gecontroleerd en verzegeld.',
                    ],
                    [
                        'icon' => '↺',
                        'title' => '30 dagen retour',
                        'body' => 'Niet overtuigd? Retour binnen 30 dagen, mits ongebruikt. Volledige terugbetaling, zonder vragen.',
                    ],
                    [
                        'icon' => '◇',
                        'title' => 'Veilig reserveren',
                        'body' => 'Uw nummer wordt vastgelegd na bevestiging. De Founding Edition wordt in één beperkte productieronde vervaardigd — volledig transparant.',
                    ],
                ],
            ],
            'faq' => [
                'sort_order' => 7,
                'eyebrow' => 'Vragen',
                'heading' => 'Veelgestelde vragen.',
            ],
            'related' => [
                'sort_order' => 8,
                'eyebrow' => 'Volgende Hoofdstukken',
                'heading' => 'De volgende nummers.',
            ],
        ];
    }

    /**
     * Normalizes both list-of-objects and list-of-strings JSON columns
     * into a list of associative rows.
     *
     * @return list<array<string, mixed>>
     */
    private function decodeJsonColumn(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $row): array => is_array($row) ? $row : ['value' => (string) $row],
            $value,
        ));
    }
};
