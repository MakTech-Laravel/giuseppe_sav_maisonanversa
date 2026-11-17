<?php

use App\Models\LegalPage;
use App\Support\Html\LegalHtml;
use App\Support\Legal\ClientApprovedLegalCopy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace Terms, Privacy and add Model withdrawal form with the
     * November 2026 client-approved NL/EN/FR copy (no DeepL).
     */
    public function up(): void
    {
        foreach (ClientApprovedLegalCopy::slugs() as $slug) {
            $bodies = ClientApprovedLegalCopy::bodiesFor($slug);
            $nl = LegalHtml::sanitize($bodies['nl']);

            $page = LegalPage::query()->firstOrNew(['slug' => $slug]);
            $page->fill([
                'body' => $nl,
                'is_published' => true,
            ])->saveQuietly();

            $this->upsertRawTranslation($page, 'en', LegalHtml::sanitize($bodies['en']), $nl);
            $this->upsertRawTranslation($page, 'fr', LegalHtml::sanitize($bodies['fr']), $nl);
        }
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
