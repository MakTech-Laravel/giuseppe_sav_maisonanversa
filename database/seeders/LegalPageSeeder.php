<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            'privacy' => <<<'MARKDOWN'
## Privacybeleid

Maison Anversa hecht waarde aan uw vertrouwen. Wij verwerken persoonsgegevens voor bestellingen, ondersteuning en nieuwsbriefcommunicatie.

## Welke gegevens

- Naam, e-mail, telefoon en leveringsadres voor bestellingen.
- Betalingsstatus via onze betaalpartner.
- Nieuwsbriefvoorkeuren wanneer u zich inschrijft.

## Uw rechten

U heeft recht op inzage, correctie, verwijdering en bezwaar. Contacteer ons via de contactpagina.
MARKDOWN,
            'terms' => <<<'MARKDOWN'
## Algemene voorwaarden

Bestellingen via maisonanversa.com vallen onder Belgisch recht. Een bestelling is definitief na bevestiging.

## Levering

Founding Edition leveringen volgen reserveringsvolgorde en geplande productie.

## Klachten

Meld problemen binnen 14 dagen na levering zodat we een passende oplossing kunnen bieden.
MARKDOWN,
            'shipping' => <<<'MARKDOWN'
## Verzending & Retour

Europa: verzending inbegrepen. Buiten Europa: op aanvraag.

## Retour

Retour binnen 14 dagen na levering, ongebruikt en met originele verpakking.

## Terugbetaling

Na controle betalen we terug via het oorspronkelijke betaalmiddel.
MARKDOWN,
            'care' => <<<'MARKDOWN'
## Zorg & Garantie

Heritage No.001 is gebouwd om lang mee te gaan.

## Onderhoud

- Leder: tweemaal per jaar neutrale balsem.
- Carbon: reinigen met zachte droge doek.
- Bewaring: droog en uit direct zonlicht.

## Garantie

Garantie dekt fabricagefouten, niet normale slijtage.
MARKDOWN,
        ];

        foreach ($pages as $slug => $body) {
            LegalPage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'body' => $body,
                    'is_published' => true,
                ],
            );
        }
    }
}
