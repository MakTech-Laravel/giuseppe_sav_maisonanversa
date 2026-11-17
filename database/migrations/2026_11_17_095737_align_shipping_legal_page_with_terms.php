<?php

use App\Models\LegalPage;
use App\Support\Html\LegalHtml;
use App\Support\Legal\AlignedShippingLegalCopy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align the Shipping & Returns page with the November 2026 Terms
     * (EU-only delivery and account/email/model-form withdrawal).
     */
    public function up(): void
    {
        $bodies = AlignedShippingLegalCopy::bodies();
        $nl = LegalHtml::sanitize($bodies['nl']);

        $page = LegalPage::query()->firstOrNew(['slug' => 'shipping']);
        $page->fill([
            'body' => $nl,
            'is_published' => true,
        ])->saveQuietly();

        $this->upsertRawTranslation($page, 'en', LegalHtml::sanitize($bodies['en']), $nl);
        $this->upsertRawTranslation($page, 'fr', LegalHtml::sanitize($bodies['fr']), $nl);
    }

    public function down(): void
    {
        //
    }

    private function upsertRawTranslation(LegalPage $page, string $locale, string $value, string $source): void
    {
        DB::table('translations')->updateOrInsert(
            [
                'translatable_type' => LegalPage::class,
                'translatable_id' => $page->id,
                'locale' => $locale,
                'column' => 'body',
            ],
            [
                'value' => $value,
                'source_hash' => hash('sha256', $source),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
};
