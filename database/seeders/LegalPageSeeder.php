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
<h2>Publiek register</h2>
<p>Als u Heritage No.001 koopt, wordt u ingeschreven in het officiële Founding Circle-register. Standaard verschijnt alleen uw nummer als Privélid. U kiest in uw account of uw volledige naam, uw voornaam en initiaal, of alleen uw nummer publiek zichtbaar is. U kunt die keuze op elk moment wijzigen of intrekken.</p>
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
<h1>Verzending &amp; Retour</h1>
<p>Elk Heritage No.001-racket wordt met de hand gecontroleerd, verpakt en verzekerd verzonden.</p>
<h2>Verzending</h2>
<p>Binnen Europa is verzending inbegrepen.</p>
<p>Elke zending is verzekerd en volgbaar. Zodra je racket vertrekt, ontvang je een e-mail met je trackinglink.</p>
<p>Buiten Europa verzenden we op aanvraag. Eventuele invoerrechten en lokale taksen zijn dan voor de koper.</p>
<h2>Bij ontvangst</h2>
<p>Controleer je pakket bij levering. Is de verpakking beschadigd of ontbreekt er iets? Laat het ons binnen 48 uur weten, met foto's, via de <a href="/nl/contact">contactpagina</a>.</p>
<h2>Retour</h2>
<p>Je hebt 14 dagen na levering om je aankoop te herroepen, zonder opgave van reden. Laat het ons binnen die termijn weten via de <a href="/nl/contact">contactpagina</a>, met je bestelnummer. Daarna heb je nog 14 dagen om het racket terug te sturen.</p>
<p>Het racket moet ongebruikt zijn: niet bespeeld, en de beschermfolie en labels zijn intact. Het moet ook volledig zijn, met het Heritage Passport, de dust bag, de rackettas en de originele verpakking.</p>
<p>De kosten van de retourzending zijn voor jou. We raden een verzekerde zending met tracking aan, want tot het racket bij ons is aangekomen, ligt het risico bij de afzender.</p>
<p>Annuleer je vóór verzending? Dan betalen we het volledige bedrag terug.</p>
<h2>De Founding Circle</h2>
<p>Je nummer wordt voor je gereserveerd zodra je bestelling betaald is. 14 dagen na levering is je inschrijving in het register definitief.</p>
<p>Stuur je het racket terug, dan komt je nummer opnieuw vrij en wordt je naam uit het register verwijderd.</p>
<h2>Terugbetaling</h2>
<p>Zodra we het racket ontvangen en gecontroleerd hebben, betalen we je terug, binnen 14 dagen, via het oorspronkelijke betaalmiddel. De oorspronkelijke standaard verzendkosten zitten daar ook in.</p>
<p>Komt het racket gebruikt, beschadigd of onvolledig terug? Dan kunnen we een vergoeding inhouden voor de waardevermindering.</p>
<p>Vragen? Neem contact met ons op via de <a href="/nl/contact">contactpagina</a>.</p>
HTML,
            'care' => <<<'HTML'
<h1>Zorg &amp; Garantie</h1>
<p>Heritage No.001 is gebouwd om lang mee te gaan. Met de juiste zorg blijft het jaren mooi en speelklaar.</p>
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
