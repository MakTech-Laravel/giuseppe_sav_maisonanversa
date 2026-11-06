<?php

use App\Models\CommerceSetting;
use App\Models\DressingItem;
use App\Models\Faq;
use App\Models\HomeHero;
use App\Models\JournalArticle;
use App\Models\LegalPage;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductSection;
use App\Models\ProductSectionItem;
use App\Models\SeoMeta;
use App\Models\StoryPage;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FOUNDING_NAME = 'Heritage Nº 001 · Founding Edition';

    /**
     * Client rule: an edition number is always written "Nº 001" (º, a space, three
     * digits), never "No.001". This rewrites the old spellings in stored copy and in
     * stored translations, and sets the founding product's name — which the opening
     * prints under the wordmark — to the client's exact text, whatever production
     * holds today. Quiet writes: no Stripe sync and no DeepL job.
     */
    public function up(): void
    {
        $this->normaliseCopy();
        $this->forceFoundingName();
    }

    public function down(): void
    {
        // The old spellings are not restored.
    }

    private function normaliseCopy(): void
    {
        $models = [
            HomeHero::class, StoryPage::class, Product::class, ProductSection::class,
            ProductSectionItem::class, ProductFaq::class, Faq::class, LegalPage::class,
            SeoMeta::class, JournalArticle::class, CommerceSetting::class,
            DressingItem::class, Translation::class,
        ];

        foreach ($models as $model) {
            $table = (new $model)->getTable();

            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getColumns($table) as $column) {
                if (! in_array($column['type_name'], ['varchar', 'text', 'tinytext', 'mediumtext', 'longtext', 'char'], true)) {
                    continue;
                }

                $name = $column['name'];

                DB::table($table)
                    ->whereNotNull($name)
                    ->where(fn ($query) => $query
                        ->where($name, 'like', '%No.%')
                        ->orWhere($name, 'like', '%N°%')
                        ->orWhere($name, 'like', '%n°%'))
                    ->orderBy(Schema::hasColumn($table, 'id') ? 'id' : $name)
                    ->each(function (object $row) use ($table, $name): void {
                        $value = (string) $row->{$name};
                        $updated = preg_replace('/(?:\bNo\.|\bN°|\bn°)\s?(?=\d{3}\b|:number)/u', 'Nº ', $value);

                        if (is_string($updated) && $updated !== $value && isset($row->id)) {
                            DB::table($table)->where('id', $row->id)->update([$name => $updated]);
                        }
                    });
            }
        }
    }

    private function forceFoundingName(): void
    {
        $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first();

        if ($product === null) {
            return;
        }

        $product->name = self::FOUNDING_NAME;
        $product->saveQuietly();

        $hash = $product->translationSourceHash('name');

        foreach ($product->translationTargetLocales() as $locale) {
            DB::table('translations')->updateOrInsert(
                [
                    'translatable_type' => Product::class,
                    'translatable_id' => $product->id,
                    'locale' => $locale,
                    'column' => 'name',
                ],
                [
                    'value' => self::FOUNDING_NAME,
                    'source_hash' => $hash,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
};
