<?php

namespace App\Support\Legal;

/**
 * Shipping & Returns page copy aligned with the November 2026 Terms
 * (EU-only delivery and the 14-day withdrawal flow).
 */
final class AlignedShippingLegalCopy
{
    /**
     * @return array{nl: string, en: string, fr: string}
     */
    public static function bodies(): array
    {
        return [
            'nl' => self::nl(),
            'en' => self::en(),
            'fr' => self::fr(),
        ];
    }

    private static function nl(): string
    {
        return <<<'HTML'
<h1>Verzending &amp; Retour</h1>
<p>Elk Heritage Nº 001-racket wordt met de hand gecontroleerd, verpakt en verzekerd verzonden.</p>
<h2>Verzending</h2>
<p>We leveren binnen de Europese Unie. Levering binnen de EU is inbegrepen in de prijs.</p>
<p>Elke zending is verzekerd en traceerbaar. Je ontvangt een e-mail met je trackinglink zodra je bestelling vertrekt.</p>
<p>Het risico op verlies of beschadiging gaat over op jou zodra je het pakket ontvangt.</p>
<h2>Bij ontvangst</h2>
<p>Controleer je pakket bij ontvangst. Is de verpakking beschadigd of ontbreekt er iets, laat het ons dan liefst binnen 48 uur weten, met foto's, via <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> of de <a href="/nl/contact">contactpagina</a>. Je wettelijke rechten blijven ook daarna bestaan.</p>
<h2>Herroeping (14 dagen)</h2>
<p>Je hebt het recht om je aankoop binnen 14 dagen zonder opgave van redenen te herroepen. Die termijn begint de dag nadat jij (of iemand die je aanduidt) het product ontvangt.</p>
<p>Je meldt je herroeping via je account (Bestellingen → Retourneren), per e-mail aan <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, of met het <a href="/nl/withdrawal">modelformulier voor herroeping</a>. Je ontvangt een ontvangstbevestiging per e-mail.</p>
<p>Na je melding heb je 14 dagen om het product terug te sturen naar het adres dat we je bezorgen. De kosten van de retourzending zijn voor jou. Het risico ligt bij jou tot het pakket bij ons aankomt; een verzekerde zending met tracking is daarom aangeraden.</p>
<p>Stuur het product terug in de staat waarin je het ontving, compleet, met het Heritage Passport, het certificaat, de dust bag en de originele verpakking.</p>
<p>Je mag het product bekijken en hanteren zoals je dat in een winkel zou doen. Is het product gebruikt (bijvoorbeeld bespeeld), beschadigd of onvolledig, dan mogen we het waardeverlies aftrekken van de terugbetaling.</p>
<p>We betalen je binnen 14 dagen na je melding terug, met hetzelfde betaalmiddel, inclusief de oorspronkelijke standaardverzendkosten. We mogen wachten met terugbetalen tot we het product hebben ontvangen of tot je aantoont dat je het hebt teruggestuurd.</p>
<p>Annuleer je vóór de verzending, dan betalen we het volledige bedrag terug.</p>
<h2>De Founding Circle</h2>
<p>Je nummer is voor jou gereserveerd zodra je betaling is ontvangen. Je nummer wordt definitief ingeschreven in het Founding Circle Register 14 dagen na de levering, als je je aankoop niet herroept.</p>
<p>Herroep je je aankoop, dan komt je nummer weer vrij en verdwijnt je naam uit het register. Het racket kan met hetzelfde nummer opnieuw verkocht worden.</p>
<h2>Meer informatie</h2>
<p>Zie ook de <a href="/nl/terms">algemene voorwaarden</a> en het <a href="/nl/withdrawal">modelformulier voor herroeping</a>.</p>
<p>Vragen? Mail <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> of gebruik de <a href="/nl/contact">contactpagina</a>.</p>
HTML;
    }

    private static function en(): string
    {
        return <<<'HTML'
<h1>Shipping &amp; Returns</h1>
<p>Every Heritage Nº 001 racket is inspected by hand, packed and shipped insured.</p>
<h2>Shipping</h2>
<p>We deliver within the European Union. Delivery within the EU is included in the price.</p>
<p>Every shipment is insured and tracked. You will receive an email with your tracking link as soon as your order ships.</p>
<p>The risk of loss or damage passes to you when you receive the parcel.</p>
<h2>On delivery</h2>
<p>Please check your parcel on receipt. If the packaging is damaged or something is missing, let us know, preferably within 48 hours, with photos, via <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> or the <a href="/en/contact">contact page</a>. Your statutory rights continue to apply after that.</p>
<h2>Right of withdrawal (14 days)</h2>
<p>You have the right to withdraw from your purchase within 14 days without giving any reason. That period starts on the day after you (or someone you designate) receive the product.</p>
<p>You notify your withdrawal through your account (Orders → Return), by email to <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, or with the <a href="/en/withdrawal">model withdrawal form</a>. You will receive an acknowledgement by email.</p>
<p>After your notice, you have 14 days to return the product to the address we give you. Return shipping costs are yours. The risk remains with you until the parcel reaches us, so an insured, tracked shipment is recommended.</p>
<p>Return the product in the condition in which you received it, complete, with the Heritage Passport, the certificate, the dust bag and the original packaging.</p>
<p>You may examine and handle the product as you would in a shop. If the product has been used (for example played with), damaged or is incomplete, we may deduct the loss in value from the refund.</p>
<p>We refund you within 14 days of your notice, using the same payment method, including the original standard shipping costs. We may withhold the refund until we have received the product or until you show that you have sent it back.</p>
<p>If you cancel before shipping, we refund the full amount.</p>
<h2>The Founding Circle</h2>
<p>Your number is reserved for you as soon as your payment is received. Your number is permanently entered in the Founding Circle Register 14 days after delivery, if you do not withdraw from your purchase.</p>
<p>If you withdraw from your purchase, your number becomes available again and your name is removed from the register. The racket may be sold again with the same number.</p>
<h2>More information</h2>
<p>See also the <a href="/en/terms">Terms &amp; Conditions</a> and the <a href="/en/withdrawal">model withdrawal form</a>.</p>
<p>Questions? Email <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> or use the <a href="/en/contact">contact page</a>.</p>
HTML;
    }

    private static function fr(): string
    {
        return <<<'HTML'
<h1>Livraison &amp; Retours</h1>
<p>Chaque raquette Heritage Nº 001 est contrôlée à la main, emballée et expédiée avec assurance.</p>
<h2>Livraison</h2>
<p>Nous livrons au sein de l’Union européenne. La livraison au sein de l’UE est incluse dans le prix.</p>
<p>Chaque envoi est assuré et suivi. Vous recevez un e-mail avec votre lien de suivi dès que votre commande est expédiée.</p>
<p>Le risque de perte ou de dommage vous est transféré dès la réception du colis.</p>
<h2>À la réception</h2>
<p>Vérifiez votre colis à la réception. Si l’emballage est endommagé ou s’il manque quelque chose, prévenez-nous de préférence dans les 48 heures, avec des photos, via <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> ou la <a href="/fr/contact">page contact</a>. Vos droits légaux restent valables au-delà.</p>
<h2>Droit de rétractation (14 jours)</h2>
<p>Vous avez le droit de vous rétracter de votre achat dans un délai de 14 jours, sans donner de motif. Ce délai commence le lendemain du jour où vous (ou une personne que vous désignez) recevez le produit.</p>
<p>Vous notifiez votre rétractation via votre compte (Commandes → Retourner), par e-mail à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, ou au moyen du <a href="/fr/withdrawal">formulaire type de rétractation</a>. Vous recevez un accusé de réception par e-mail.</p>
<p>Après votre notification, vous disposez de 14 jours pour renvoyer le produit à l’adresse que nous vous communiquons. Les frais de retour sont à votre charge. Le risque vous incombe jusqu’à ce que le colis nous parvienne ; un envoi assuré et suivi est donc recommandé.</p>
<p>Renvoyez le produit dans l’état où vous l’avez reçu, complet, avec le Heritage Passport, le certificat, la housse et l’emballage d’origine.</p>
<p>Vous pouvez examiner et manipuler le produit comme vous le feriez en magasin. Si le produit a été utilisé (par exemple pour jouer), endommagé ou est incomplet, nous pouvons déduire la dépréciation du remboursement.</p>
<p>Nous vous remboursons dans les 14 jours suivant votre notification, par le même moyen de paiement, frais de livraison standard initiaux compris. Nous pouvons différer le remboursement jusqu’à réception du produit ou jusqu’à ce que vous prouviez l’avoir renvoyé.</p>
<p>Si vous annulez avant l’expédition, nous remboursons le montant total.</p>
<h2>Le Founding Circle</h2>
<p>Votre numéro vous est réservé dès réception de votre paiement. Votre numéro est inscrit définitivement au Founding Circle Register 14 jours après la livraison, si vous ne vous rétractez pas.</p>
<p>Si vous vous rétractez, votre numéro redevient disponible et votre nom est retiré du registre. La raquette peut être revendue avec le même numéro.</p>
<h2>Plus d’informations</h2>
<p>Voir aussi les <a href="/fr/terms">conditions générales</a> et le <a href="/fr/withdrawal">formulaire type de rétractation</a>.</p>
<p>Des questions ? Écrivez à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> ou utilisez la <a href="/fr/contact">page contact</a>.</p>
HTML;
    }
}
