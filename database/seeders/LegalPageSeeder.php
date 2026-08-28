<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Support\Html\LegalHtml;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            'privacy' => <<<'HTML'
<h2>Privacybeleid</h2>
<p>Maison Anversa hecht waarde aan uw vertrouwen. Wij verwerken persoonsgegevens voor bestellingen, ondersteuning en nieuwsbriefcommunicatie.</p>
<h2>Welke gegevens</h2>
<ul>
<li>Naam, e-mail, telefoon en leveringsadres voor bestellingen.</li>
<li>Betalingsstatus via onze betaalpartner.</li>
<li>Nieuwsbriefvoorkeuren wanneer u zich inschrijft.</li>
</ul>
<h2>Uw rechten</h2>
<p>U heeft recht op inzage, correctie, verwijdering en bezwaar.</p>
HTML,
            'terms' => <<<'HTML'
<h2>Algemene voorwaarden</h2>
<p>Bestellingen via maisonanversa.com vallen onder Belgisch recht. Een bestelling is definitief na bevestiging.</p>
<h2>Levering</h2>
<p>Founding Edition leveringen volgen reserveringsvolgorde en geplande productie.</p>
<h2>Klachten</h2>
<p>Meld problemen binnen 14 dagen na levering zodat we een passende oplossing kunnen bieden.</p>
HTML,
            'shipping' => <<<'HTML'
<h2>Verzending &amp; Retour</h2>
<p>Europa: verzending inbegrepen. Buiten Europa: op aanvraag.</p>
<h2>Retour</h2>
<p>Retour binnen 14 dagen na levering, ongebruikt en met originele verpakking.</p>
<h2>Terugbetaling</h2>
<p>Na controle betalen we terug via het oorspronkelijke betaalmiddel.</p>
HTML,
            'care' => <<<'HTML'
<h2>Zorg &amp; Garantie</h2>
<p>Heritage No.001 is gebouwd om lang mee te gaan.</p>
<h2>Onderhoud</h2>
<ul>
<li>Leder: tweemaal per jaar neutrale balsem.</li>
<li>Carbon: reinigen met zachte droge doek.</li>
<li>Bewaring: droog en uit direct zonlicht.</li>
</ul>
<h2>Garantie</h2>
<p>Garantie dekt fabricagefouten, niet normale slijtage.</p>
HTML,
        ];

        foreach ($pages as $slug => $body) {
            LegalPage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'body' => LegalHtml::sanitize($body),
                    'is_published' => true,
                ],
            );
        }
    }
}
