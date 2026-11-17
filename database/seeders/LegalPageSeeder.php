<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Support\Html\LegalHtml;
use App\Support\Legal\AlignedShippingLegalCopy;
use App\Support\Legal\ClientApprovedLegalCopy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegalPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ClientApprovedLegalCopy::slugs() as $slug) {
            $this->seedLocalizedPage($slug, ClientApprovedLegalCopy::bodiesFor($slug));
        }

        $this->seedLocalizedPage('shipping', AlignedShippingLegalCopy::bodies());

        LegalPage::query()->updateOrCreate(
            ['slug' => 'care'],
            [
                'body' => LegalHtml::sanitize($this->careNl()),
                'is_published' => true,
            ],
        );
    }

    /**
     * @param  array{nl: string, en: string, fr: string}  $bodies
     */
    private function seedLocalizedPage(string $slug, array $bodies): void
    {
        $nl = LegalHtml::sanitize($bodies['nl']);

        $page = LegalPage::query()->firstOrNew(['slug' => $slug]);
        $page->fill([
            'body' => $nl,
            'is_published' => true,
        ])->saveQuietly();

        $this->upsertTranslation($page, 'en', LegalHtml::sanitize($bodies['en']), $nl);
        $this->upsertTranslation($page, 'fr', LegalHtml::sanitize($bodies['fr']), $nl);
    }

    private function upsertTranslation(LegalPage $page, string $locale, string $value, string $source): void
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

    private function careNl(): string
    {
        return <<<'HTML'
<h1>Zorg &amp; Garantie</h1>
<p>Heritage Nº 001 is gebouwd om lang mee te gaan. Met de juiste zorg blijft het jaren mooi en speelklaar.</p>
<h2>Het slagvlak</h2>
<p>Reinig het matte oppervlak en het reliëf van het huis met een zachte, droge doek.</p>
<p>Gebruik geen water, zeep, alcohol of oplosmiddelen, en schrob niet over het reliëf.</p>
<h2>De lederen grip</h2>
<p>Laat de grip na het spelen aan de lucht drogen.</p>
<p>Verzorg het leder twee keer per jaar met een neutrale lederbalsem.</p>
<p>Leder werkt en verkleurt licht met de tijd. Dat hoort bij het karakter van het materiaal.</p>
<h2>Bewaring</h2>
<p>Bewaar je racket in zijn tas of dust bag, op een droge plaats en uit direct zonlicht.</p>
<p>Laat het nooit in een warme auto liggen. Hitte kan de kern van EVA aantasten.</p>
<h2>Garantie</h2>
<p>Je hebt recht op de wettelijke garantie van 2 jaar vanaf levering. Die dekt fabricagefouten en gebreken die al aanwezig waren bij levering.</p>
<p>Niet gedekt zijn normale slijtage van de grip en het oppervlak, krassen, schade door een slag tegen de muur, de vloer of een ander racket, barsten door impact, schade door verkeerde bewaring zoals hitte of vocht, en aanpassingen aan het racket.</p>
<p>Een garantievraag? Stuur ons via de <a href="/nl/contact">contactpagina</a> je bestelnummer, het serienummer uit je Heritage Passport (Nº 001–100) en duidelijke foto's. We bekijken elke vraag persoonlijk en zoeken een oplossing: herstelling, vervanging of terugbetaling, zoals de wet voorziet.</p>
<p>Bewaar je Heritage Passport goed. Het is je bewijs van echtheid en van aankoop.</p>
<p>Vragen? Neem contact met ons op via de <a href="/nl/contact">contactpagina</a>.</p>
HTML;
    }
}
