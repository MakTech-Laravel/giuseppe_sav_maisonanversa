<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_NAME = 'Heritage No.001 · Founding Edition';

    private const NEW_NAME = 'Heritage Nº 001 · Founding Edition';

    /**
     * The opening prints the opening product's name under the wordmark, and the
     * client writes it "Heritage Nº 001 · Founding Edition". Renames the founding
     * product, and its English and French name, which are stored rows and would
     * otherwise keep the old spelling. Nothing else on the product is touched, so
     * sections and FAQs an admin has edited stay as they are. A name an admin has
     * already changed to something else is left alone.
     */
    public function up(): void
    {
        $this->rename(self::OLD_NAME, self::NEW_NAME);
    }

    public function down(): void
    {
        $this->rename(self::NEW_NAME, self::OLD_NAME);
    }

    private function rename(string $from, string $to): void
    {
        $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first();

        if ($product === null || $product->name !== $from) {
            return;
        }

        // Quietly: no Stripe sync and no DeepL job for a name that is the same in every language.
        $product->name = $to;
        $product->saveQuietly();

        $hash = hash('sha256', $to);

        foreach ($product->translationTargetLocales() as $locale) {
            DB::table('translations')->updateOrInsert(
                [
                    'translatable_type' => Product::class,
                    'translatable_id' => $product->id,
                    'locale' => $locale,
                    'column' => 'name',
                ],
                [
                    'value' => $to,
                    'source_hash' => $hash,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
};
