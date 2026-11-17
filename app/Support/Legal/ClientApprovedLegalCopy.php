<?php

namespace App\Support\Legal;

use InvalidArgumentException;

/**
 * Client-approved legal page bodies (November 2026) for the terms,
 * withdrawal and privacy pages, in Dutch, English and French.
 */
final class ClientApprovedLegalCopy
{
    /**
     * @var list<string>
     */
    private const SLUGS = ['terms', 'withdrawal', 'privacy'];

    /**
     * @var list<string>
     */
    private const LOCALES = ['nl', 'en', 'fr'];

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return self::SLUGS;
    }

    /**
     * @return array{nl: string, en: string, fr: string}
     */
    public static function bodiesFor(string $slug): array
    {
        return [
            'nl' => self::body($slug, 'nl'),
            'en' => self::body($slug, 'en'),
            'fr' => self::body($slug, 'fr'),
        ];
    }

    public static function body(string $slug, string $locale): string
    {
        if (! in_array($slug, self::SLUGS, true)) {
            throw new InvalidArgumentException("Unknown legal page slug [{$slug}].");
        }

        if (! in_array($locale, self::LOCALES, true)) {
            throw new InvalidArgumentException("Unsupported legal page locale [{$locale}].");
        }

        return trim(match ("{$slug}.{$locale}") {
            'terms.nl' => self::termsNl(),
            'terms.en' => self::termsEn(),
            'terms.fr' => self::termsFr(),
            'withdrawal.nl' => self::withdrawalNl(),
            'withdrawal.en' => self::withdrawalEn(),
            'withdrawal.fr' => self::withdrawalFr(),
            'privacy.nl' => self::privacyNl(),
            'privacy.en' => self::privacyEn(),
            'privacy.fr' => self::privacyFr(),
        });
    }

    private static function termsEn(): string
    {
        return <<<'HTML'
<h2>1. Who we are</h2>
<p>Maison Anversa (sole proprietorship of Yusuf Savran) · Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgium · Company number: 1019.601.147 · VAT: BE1019601147 · Email: <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> · Website: maisonanversa.com</p>
<p>These terms apply to every order placed on maisonanversa.com and to the use of your account and the community. They are available in Dutch, English and French.</p>
<h2>2. Ordering</h2>
<ol>
<li>You order through the website. Your contract with us is formed once you have completed payment and receive our confirmation by email.</li>
<li>You must be at least 18 years old to order.</li>
<li>Obvious errors in prices or product information do not bind us. If we notice such an error after your order, we will contact you; you may then cancel free of charge.</li>
</ol>
<h2>3. Prices and payment</h2>
<ol>
<li>All prices are in euros, including VAT.</li>
<li>Delivery within the European Union is included.</li>
<li>You pay the full amount when you order, using the payment methods on the website. Payments are processed by our payment partner Stripe.</li>
</ol>
<h2>4. Pre-sale of Heritage Nº 001</h2>
<ol>
<li>Heritage Nº 001 is sold as a pre-sale. You pay when you order; the racket is made in a single limited run of 100 numbered pieces.</li>
<li>The expected delivery is shown on the product page (currently: first quarter of 2027). We will keep you informed if that period changes.</li>
<li>If your racket has not been delivered by 30 June 2027, you may cancel your order free of charge and we will refund the full amount within 14 days.</li>
<li>You choose your number at checkout. Your number is reserved for you as soon as your payment is received.</li>
</ol>
<h2>5. Delivery</h2>
<ol>
<li>We deliver within the European Union, to the address you give with your order.</li>
<li>Every shipment is insured and tracked. You will receive an email with your tracking link as soon as your order ships.</li>
<li>The risk of loss or damage passes to you when you receive the parcel.</li>
<li>Please check your parcel on receipt. If the packaging is damaged or something is missing, let us know, preferably within 48 hours, with photos. Your statutory rights continue to apply after that.</li>
</ol>
<h2>6. Right of withdrawal (14 days)</h2>
<ol>
<li>You have the right to withdraw from your purchase within 14 days without giving any reason. That period starts on the day after you (or someone you designate) receive the product.</li>
<li>You notify your withdrawal through your account (Orders → Return), by email to <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, or with the <a href="/en/withdrawal">model form</a> below. You will receive an acknowledgement by email.</li>
<li>After your notice, you have 14 days to return the product to the address we give you. Return shipping costs are yours. The risk remains with you until the parcel reaches us, so an insured, tracked shipment is recommended.</li>
<li>Return the product in the condition in which you received it, complete, with the Heritage Passport, the certificate, the dust bag and the original packaging.</li>
<li>You may examine and handle the product as you would in a shop. If the product has been used (for example played with), damaged or is incomplete, we may deduct the loss in value from the refund.</li>
<li>We refund you within 14 days of your notice, using the same payment method, including the original standard shipping costs. We may withhold the refund until we have received the product or until you show that you have sent it back.</li>
<li>If you cancel before shipping, we refund the full amount.</li>
</ol>
<h2>7. Legal guarantee</h2>
<ol>
<li>You are entitled to the legal guarantee of 2 years from delivery, for defects that existed at the time of delivery.</li>
<li>A defect that appears within the first year after delivery is presumed to have existed at delivery, unless we prove otherwise.</li>
<li>Report a defect within 2 months of discovering it, to <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, with your order number, the number from your Heritage Passport and photos.</li>
<li>We will repair or replace the product. If that is not possible or not reasonable, you will receive a price reduction or a refund, as the law provides.</li>
<li>The guarantee does not cover normal wear (such as of the grip and the surface), damage from impacts against walls, floor or other rackets, damage from improper storage (such as heat or moisture), or modifications to the product. More information is on the <a href="/en/care">Care &amp; Guarantee</a> page.</li>
</ol>
<h2>8. Founding Circle and the Register</h2>
<ol>
<li>Every buyer of Heritage Nº 001 becomes a member of the Founding Circle. Membership is personal.</li>
<li>Your number is permanently entered in the Founding Circle Register 14 days after delivery, if you do not withdraw from your purchase.</li>
<li>By default, the public register shows only your number (“Private Member”). In your account you choose whether your full name or your first name and initial are shown. You can change that choice at any time.</li>
<li>If you withdraw from your purchase, your number becomes available again and your name is removed from the register. The racket may be sold again with the same number.</li>
<li>We offer the benefits of the Founding Circle (such as priority access to new products and invitations) for as long as Maison Anversa organises those activities. They are not part of the purchase price.</li>
</ol>
<h2>9. Account, community and sessions</h2>
<ol>
<li>To join the community you create an account. You must be at least 16 years old. You keep your login details secret and are responsible for what happens with your account.</li>
<li>Through the session planner, members can create and join padel sessions. Maison Anversa only puts players in touch with each other. We do not organise the sessions, do not book courts and are not a party to arrangements between players or with clubs.</li>
<li>You play at your own risk. Make sure you have suitable insurance. Maison Anversa is not liable for injury, damage or loss during a session, except in case of intent or gross negligence on our part.</li>
<li>Be respectful in the chat, on your profile and with scores. Offensive, discriminatory, misleading or commercial messages, and sharing other people’s data without their consent, are not allowed.</li>
<li>We may remove messages and temporarily or permanently block an account in case of misuse. You can delete your account yourself at any time.</li>
</ol>
<h2>10. Intellectual property</h2>
<p>The Maison Anversa name and logo, the house illustration, and the texts, photos and designs on this website are protected. You may not use, copy or distribute them without our written permission.</p>
<h2>11. Liability</h2>
<ol>
<li>We do our best to keep the website accurate and available, but cannot guarantee that it will always work without errors or interruptions.</li>
<li>Our liability is limited to the amount of the order concerned, except in case of intent, gross negligence or personal injury. Your statutory rights as a consumer always apply.</li>
<li>We are not liable for delay or failure to perform an obligation due to force majeure, such as strikes, problems with suppliers or carriers, natural disasters or government measures. We will keep you informed, and for a delay in the pre-sale, article 4.3 applies.</li>
</ol>
<h2>12. Complaints and disputes</h2>
<ol>
<li>A question or complaint? Email <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. We reply within one working day and will look for a solution with you.</li>
<li>If we cannot resolve it together, you can contact the Consumer Ombudsman Service (<a href="https://consumerombudsman.be">consumerombudsman.be</a>).</li>
<li>These terms are governed by Belgian law. If you live in another EU country, you keep the protection that the law of your country gives you as a consumer. Disputes are brought before the competent court as provided by law.</li>
</ol>
<h2>13. Changes</h2>
<p>We may change these terms. Your order is governed by the terms as they appeared on the website at the time you ordered.</p>
HTML;
    }

    private static function termsNl(): string
    {
        return <<<'HTML'
<h2>1. Wie we zijn</h2>
<p>Maison Anversa (eenmanszaak van Yusuf Savran) · Graaf de Broquevillestraat 29 bus 301, 2400 Mol, België · Ondernemingsnummer: 1019.601.147 · Btw: BE1019601147 · E-mail: <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> · Website: maisonanversa.com</p>
<p>Deze voorwaarden gelden voor elke bestelling via maisonanversa.com en voor het gebruik van je account en de community. Ze zijn beschikbaar in het Nederlands, Engels en Frans.</p>
<h2>2. Bestellen</h2>
<ol>
<li>Je bestelt via de website. Je overeenkomst met ons komt tot stand zodra je de betaling hebt afgerond en je onze bevestiging per e-mail ontvangt.</li>
<li>Je moet minstens 18 jaar zijn om te bestellen.</li>
<li>Kennelijke fouten in prijzen of productinformatie binden ons niet. Merken we zo'n fout na je bestelling, dan nemen we contact met je op; je kunt dan kosteloos annuleren.</li>
</ol>
<h2>3. Prijzen en betaling</h2>
<ol>
<li>Alle prijzen zijn in euro, inclusief btw.</li>
<li>Levering binnen de Europese Unie is inbegrepen.</li>
<li>Je betaalt het volledige bedrag bij je bestelling, via de betaalmethodes op de website. Betalingen verlopen via onze betaalpartner Stripe.</li>
</ol>
<h2>4. Voorverkoop van Heritage Nº 001</h2>
<ol>
<li>Heritage Nº 001 wordt verkocht in voorverkoop. Je betaalt bij je bestelling; het racket wordt geproduceerd in één beperkte productie van 100 genummerde stuks.</li>
<li>De verwachte levering staat bij het product (momenteel: eerste kwartaal 2027). We houden je op de hoogte als die periode verschuift.</li>
<li>Is je racket niet geleverd op 30 juni 2027, dan kun je je bestelling kosteloos annuleren en betalen we het volledige bedrag terug binnen 14 dagen.</li>
<li>Je kiest je nummer bij het afrekenen. Je nummer is voor jou gereserveerd zodra je betaling is ontvangen.</li>
</ol>
<h2>5. Levering</h2>
<ol>
<li>We leveren binnen de Europese Unie, op het adres dat je bij je bestelling opgeeft.</li>
<li>Elke zending is verzekerd en traceerbaar. Je ontvangt een e-mail met je trackinglink zodra je bestelling vertrekt.</li>
<li>Het risico op verlies of beschadiging gaat over op jou zodra je het pakket ontvangt.</li>
<li>Controleer je pakket bij ontvangst. Is de verpakking beschadigd of ontbreekt er iets, laat het ons dan liefst binnen 48 uur weten, met foto's. Je wettelijke rechten blijven ook daarna bestaan.</li>
</ol>
<h2>6. Herroepingsrecht (14 dagen)</h2>
<ol>
<li>Je hebt het recht om je aankoop binnen 14 dagen zonder opgave van redenen te herroepen. Die termijn begint de dag nadat jij (of iemand die je aanduidt) het product ontvangt.</li>
<li>Je meldt je herroeping via je account (Bestellingen → Retourneren), per e-mail aan <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, of met het <a href="/nl/withdrawal">modelformulier</a> hieronder. Je ontvangt een ontvangstbevestiging per e-mail.</li>
<li>Na je melding heb je 14 dagen om het product terug te sturen naar het adres dat we je bezorgen. De kosten van de retourzending zijn voor jou. Het risico ligt bij jou tot het pakket bij ons aankomt; een verzekerde zending met tracking is daarom aangeraden.</li>
<li>Stuur het product terug in de staat waarin je het ontving, compleet, met het Heritage Passport, het certificaat, de dust bag en de originele verpakking.</li>
<li>Je mag het product bekijken en hanteren zoals je dat in een winkel zou doen. Is het product gebruikt (bijvoorbeeld bespeeld), beschadigd of onvolledig, dan mogen we het waardeverlies aftrekken van de terugbetaling.</li>
<li>We betalen je binnen 14 dagen na je melding terug, met hetzelfde betaalmiddel, inclusief de oorspronkelijke standaardverzendkosten. We mogen wachten met terugbetalen tot we het product hebben ontvangen of tot je aantoont dat je het hebt teruggestuurd.</li>
<li>Annuleer je vóór de verzending, dan betalen we het volledige bedrag terug.</li>
</ol>
<h2>7. Wettelijke garantie</h2>
<ol>
<li>Je hebt recht op de wettelijke garantie van 2 jaar vanaf de levering, voor gebreken die bestonden op het moment van levering.</li>
<li>Een gebrek dat zich binnen het eerste jaar na levering toont, wordt verondersteld al aanwezig te zijn bij levering, tenzij we het tegendeel aantonen.</li>
<li>Meld een gebrek binnen 2 maanden nadat je het vaststelde, via <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, met je bestelnummer, het nummer uit je Heritage Passport en foto's.</li>
<li>We herstellen of vervangen het product. Is dat niet mogelijk of niet redelijk, dan krijg je een prijsvermindering of een terugbetaling, zoals de wet bepaalt.</li>
<li>De garantie dekt geen normale slijtage (zoals van de grip en het oppervlak), schade door slagen tegen wand, vloer of andere rackets, schade door verkeerde bewaring (zoals hitte of vocht), of aanpassingen aan het product. Meer uitleg vind je op de pagina <a href="/nl/care">Zorg &amp; Garantie</a>.</li>
</ol>
<h2>8. Founding Circle en het register</h2>
<ol>
<li>Elke koper van Heritage Nº 001 wordt lid van de Founding Circle. Het lidmaatschap is persoonlijk.</li>
<li>Je nummer wordt definitief ingeschreven in het Founding Circle Register 14 dagen na de levering, als je je aankoop niet herroept.</li>
<li>In het openbare register verschijnt standaard enkel je nummer (“Private Member”). In je account kies je zelf of je volledige naam of je voornaam en initiaal zichtbaar worden. Je kunt die keuze altijd wijzigen.</li>
<li>Herroep je je aankoop, dan komt je nummer weer vrij en verdwijnt je naam uit het register. Het racket kan met hetzelfde nummer opnieuw verkocht worden.</li>
<li>De voordelen van de Founding Circle (zoals voorrang bij nieuwe producten en uitnodigingen) bieden we aan zolang Maison Anversa die activiteiten organiseert. Ze zijn geen deel van de koopprijs.</li>
</ol>
<h2>9. Account, community en sessies</h2>
<ol>
<li>Voor de community maak je een account aan. Je moet minstens 16 jaar zijn. Je houdt je inloggegevens geheim en bent verantwoordelijk voor wat er met je account gebeurt.</li>
<li>Via de sessieplanner kunnen leden padelsessies aanmaken en eraan deelnemen. Maison Anversa brengt spelers enkel met elkaar in contact. We zijn geen organisator van de sessies, reserveren geen banen en zijn geen partij bij afspraken tussen spelers of met clubs.</li>
<li>Je speelt op eigen risico. Zorg voor een passende verzekering. Maison Anversa is niet aansprakelijk voor letsels, schade of verlies tijdens een sessie, behalve bij opzet of grove fout van onze kant.</li>
<li>In de chat, op je profiel en bij scores gedraag je je respectvol. Beledigende, discriminerende, misleidende of commerciële berichten, en het delen van gegevens van anderen zonder hun toestemming, zijn niet toegestaan.</li>
<li>We mogen berichten verwijderen en een account tijdelijk of definitief blokkeren bij misbruik. Je kunt je account op elk moment zelf verwijderen.</li>
</ol>
<h2>10. Intellectuele eigendom</h2>
<p>De naam en het logo van Maison Anversa, de huisillustratie, de teksten, foto's en ontwerpen op deze website zijn beschermd. Je mag ze niet zonder onze schriftelijke toestemming gebruiken, kopiëren of verspreiden.</p>
<h2>11. Aansprakelijkheid</h2>
<ol>
<li>We doen ons best om de website correct en beschikbaar te houden, maar kunnen niet garanderen dat ze altijd foutloos of zonder onderbreking werkt.</li>
<li>Onze aansprakelijkheid is beperkt tot het bedrag van de betrokken bestelling, behalve bij opzet, grove fout, of schade aan personen. Je wettelijke rechten als consument blijven altijd gelden.</li>
<li>We zijn niet aansprakelijk voor vertraging of het niet nakomen van een verplichting door overmacht, zoals stakingen, problemen bij leveranciers of vervoerders, natuurrampen of maatregelen van de overheid. We houden je dan op de hoogte, en bij een vertraging van de voorverkoop geldt artikel 4.3.</li>
</ol>
<h2>12. Klachten en geschillen</h2>
<ol>
<li>Een vraag of klacht? Mail naar <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. We antwoorden binnen één werkdag en zoeken samen met jou een oplossing.</li>
<li>Komen we er samen niet uit, dan kun je terecht bij de Consumentenombudsdienst (<a href="https://consumentenombudsdienst.be">consumentenombudsdienst.be</a>).</li>
<li>Op deze voorwaarden is het Belgisch recht van toepassing. Woon je in een ander EU-land, dan behoud je de bescherming die de wet van jouw land je als consument geeft. Geschillen worden voorgelegd aan de bevoegde rechtbank volgens de wet.</li>
</ol>
<h2>13. Wijzigingen</h2>
<p>We kunnen deze voorwaarden aanpassen. Voor je bestelling gelden de voorwaarden zoals ze op de website stonden op het moment dat je bestelde.</p>
HTML;
    }

    private static function termsFr(): string
    {
        return <<<'HTML'
<h2>1. Qui sommes-nous</h2>
<p>Maison Anversa (entreprise individuelle de Yusuf Savran) · Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgique · Numéro d’entreprise : 1019.601.147 · TVA : BE1019601147 · E-mail : <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a> · Site : maisonanversa.com</p>
<p>Les présentes conditions s’appliquent à toute commande passée sur maisonanversa.com ainsi qu’à l’utilisation de votre compte et de la communauté. Elles sont disponibles en néerlandais, en anglais et en français.</p>
<h2>2. Commander</h2>
<ol>
<li>Vous commandez via le site. Le contrat est conclu dès que vous avez finalisé le paiement et reçu notre confirmation par e-mail.</li>
<li>Vous devez avoir au moins 18 ans pour commander.</li>
<li>Les erreurs manifestes de prix ou d’information produit ne nous engagent pas. Si nous constatons une telle erreur après votre commande, nous vous contactons ; vous pouvez alors annuler sans frais.</li>
</ol>
<h2>3. Prix et paiement</h2>
<ol>
<li>Tous les prix sont en euros, TVA comprise.</li>
<li>La livraison au sein de l’Union européenne est incluse.</li>
<li>Vous payez le montant total lors de la commande, via les moyens de paiement proposés sur le site. Les paiements sont traités par notre partenaire de paiement Stripe.</li>
</ol>
<h2>4. Prévente de Heritage Nº 001</h2>
<ol>
<li>Heritage Nº 001 est vendue en prévente. Vous payez à la commande ; la raquette est fabriquée en une seule série limitée de 100 pièces numérotées.</li>
<li>La livraison prévue est indiquée sur la page du produit (actuellement : premier trimestre 2027). Nous vous tenons informé si cette période change.</li>
<li>Si votre raquette n’est pas livrée au 30 juin 2027, vous pouvez annuler votre commande sans frais et nous vous remboursons le montant total dans les 14 jours.</li>
<li>Vous choisissez votre numéro lors du paiement. Votre numéro vous est réservé dès réception de votre paiement.</li>
</ol>
<h2>5. Livraison</h2>
<ol>
<li>Nous livrons au sein de l’Union européenne, à l’adresse indiquée lors de votre commande.</li>
<li>Chaque envoi est assuré et suivi. Vous recevez un e-mail avec votre lien de suivi dès que votre commande est expédiée.</li>
<li>Le risque de perte ou de dommage vous est transféré dès la réception du colis.</li>
<li>Vérifiez votre colis à la réception. Si l’emballage est endommagé ou s’il manque quelque chose, prévenez-nous de préférence dans les 48 heures, avec des photos. Vos droits légaux restent valables au-delà.</li>
</ol>
<h2>6. Droit de rétractation (14 jours)</h2>
<ol>
<li>Vous avez le droit de vous rétracter de votre achat dans un délai de 14 jours, sans donner de motif. Ce délai commence le lendemain du jour où vous (ou une personne que vous désignez) recevez le produit.</li>
<li>Vous notifiez votre rétractation via votre compte (Commandes → Retourner), par e-mail à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, ou au moyen du <a href="/fr/withdrawal">formulaire type</a> ci-dessous. Vous recevez un accusé de réception par e-mail.</li>
<li>Après votre notification, vous disposez de 14 jours pour renvoyer le produit à l’adresse que nous vous communiquons. Les frais de retour sont à votre charge. Le risque vous incombe jusqu’à ce que le colis nous parvienne ; un envoi assuré et suivi est donc recommandé.</li>
<li>Renvoyez le produit dans l’état où vous l’avez reçu, complet, avec le Heritage Passport, le certificat, la housse et l’emballage d’origine.</li>
<li>Vous pouvez examiner et manipuler le produit comme vous le feriez en magasin. Si le produit a été utilisé (par exemple pour jouer), endommagé ou est incomplet, nous pouvons déduire la dépréciation du remboursement.</li>
<li>Nous vous remboursons dans les 14 jours suivant votre notification, par le même moyen de paiement, frais de livraison standard initiaux compris. Nous pouvons différer le remboursement jusqu’à réception du produit ou jusqu’à ce que vous prouviez l’avoir renvoyé.</li>
<li>Si vous annulez avant l’expédition, nous remboursons le montant total.</li>
</ol>
<h2>7. Garantie légale</h2>
<ol>
<li>Vous bénéficiez de la garantie légale de 2 ans à compter de la livraison, pour les défauts existant au moment de la livraison.</li>
<li>Un défaut qui apparaît au cours de la première année suivant la livraison est présumé exister lors de la livraison, sauf preuve contraire de notre part.</li>
<li>Signalez un défaut dans les 2 mois suivant sa constatation, à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>, avec votre numéro de commande, le numéro de votre Heritage Passport et des photos.</li>
<li>Nous réparons ou remplaçons le produit. Si cela n’est pas possible ou pas raisonnable, vous obtenez une réduction de prix ou un remboursement, conformément à la loi.</li>
<li>La garantie ne couvre pas l’usure normale (comme celle du grip et de la surface), les dommages dus à des chocs contre les murs, le sol ou d’autres raquettes, les dommages dus à un mauvais stockage (comme la chaleur ou l’humidité), ni les modifications apportées au produit. Plus d’informations sur la page <a href="/fr/care">Entretien &amp; Garantie</a>.</li>
</ol>
<h2>8. Founding Circle et le registre</h2>
<ol>
<li>Chaque acheteur de Heritage Nº 001 devient membre du Founding Circle. L’adhésion est personnelle.</li>
<li>Votre numéro est inscrit définitivement au Founding Circle Register 14 jours après la livraison, si vous ne vous rétractez pas.</li>
<li>Par défaut, le registre public n’affiche que votre numéro (« Private Member »). Dans votre compte, vous choisissez d’afficher votre nom complet ou votre prénom et votre initiale. Vous pouvez modifier ce choix à tout moment.</li>
<li>Si vous vous rétractez, votre numéro redevient disponible et votre nom est retiré du registre. La raquette peut être revendue avec le même numéro.</li>
<li>Nous offrons les avantages du Founding Circle (comme l’accès prioritaire aux nouveaux produits et des invitations) tant que Maison Anversa organise ces activités. Ils ne font pas partie du prix d’achat.</li>
</ol>
<h2>9. Compte, communauté et sessions</h2>
<ol>
<li>Pour la communauté, vous créez un compte. Vous devez avoir au moins 16 ans. Vous gardez vos identifiants secrets et êtes responsable de ce qui se passe avec votre compte.</li>
<li>Via le planificateur de sessions, les membres peuvent créer des sessions de padel et y participer. Maison Anversa met uniquement les joueurs en contact. Nous n’organisons pas les sessions, ne réservons pas de terrains et ne sommes pas partie aux arrangements entre joueurs ou avec les clubs.</li>
<li>Vous jouez à vos propres risques. Veillez à disposer d’une assurance adaptée. Maison Anversa n’est pas responsable des blessures, dommages ou pertes survenus pendant une session, sauf en cas de faute intentionnelle ou de faute grave de notre part.</li>
<li>Dans le chat, sur votre profil et pour les scores, vous vous comportez avec respect. Les messages injurieux, discriminatoires, trompeurs ou commerciaux, ainsi que le partage des données d’autrui sans leur consentement, sont interdits.</li>
<li>Nous pouvons supprimer des messages et bloquer un compte temporairement ou définitivement en cas d’abus. Vous pouvez supprimer votre compte vous-même à tout moment.</li>
</ol>
<h2>10. Propriété intellectuelle</h2>
<p>Le nom et le logo Maison Anversa, l’illustration de la maison, ainsi que les textes, photos et créations de ce site sont protégés. Vous ne pouvez pas les utiliser, copier ou diffuser sans notre autorisation écrite.</p>
<h2>11. Responsabilité</h2>
<ol>
<li>Nous faisons de notre mieux pour que le site soit exact et disponible, mais ne pouvons garantir qu’il fonctionne toujours sans erreur ni interruption.</li>
<li>Notre responsabilité est limitée au montant de la commande concernée, sauf en cas de faute intentionnelle, de faute grave ou de dommage corporel. Vos droits légaux de consommateur s’appliquent toujours.</li>
<li>Nous ne sommes pas responsables d’un retard ou d’une inexécution dus à un cas de force majeure, tel que grèves, problèmes chez des fournisseurs ou transporteurs, catastrophes naturelles ou mesures des autorités. Nous vous tenons alors informé, et en cas de retard de la prévente, l’article 4.3 s’applique.</li>
</ol>
<h2>12. Réclamations et litiges</h2>
<ol>
<li>Une question ou une réclamation ? Écrivez à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. Nous répondons dans un jour ouvrable et cherchons une solution avec vous.</li>
<li>Si nous ne trouvons pas de solution ensemble, vous pouvez vous adresser au Service de Médiation pour le Consommateur (<a href="https://mediationconsommateur.be">mediationconsommateur.be</a>).</li>
<li>Les présentes conditions sont régies par le droit belge. Si vous résidez dans un autre pays de l’UE, vous conservez la protection que vous accorde la loi de votre pays en tant que consommateur. Les litiges sont portés devant le tribunal compétent conformément à la loi.</li>
</ol>
<h2>13. Modifications</h2>
<p>Nous pouvons modifier ces conditions. Votre commande est régie par les conditions telles qu’elles figuraient sur le site au moment de votre commande.</p>
HTML;
    }

    private static function withdrawalEn(): string
    {
        return <<<'HTML'
<p>Only complete this form if you wish to withdraw from your purchase. You can also simply use the “Return” button in your account.</p>
<p>To: Maison Anversa (sole proprietorship of Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgium · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a></p>
<p>I hereby give notice that I withdraw from my contract for the sale of the following goods:</p>
<p>Product and number: ______________________</p>
<p>Ordered on: ____________ · Received on: ____________</p>
<p>Order number: ______________________</p>
<p>Name: ______________________</p>
<p>Address: ______________________</p>
<p>Signature (only if this form is on paper): ____________</p>
<p>Date: ____________</p>
HTML;
    }

    private static function withdrawalNl(): string
    {
        return <<<'HTML'
<p>Vul dit formulier enkel in als je je aankoop wilt herroepen. Je kunt ook gewoon de knop “Retourneren” in je account gebruiken.</p>
<p>Aan: Maison Anversa (eenmanszaak van Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, België · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a></p>
<p>Ik deel u hierbij mee dat ik onze overeenkomst betreffende de verkoop van de volgende goederen herroep:</p>
<p>Product en nummer: ______________________</p>
<p>Besteld op: ____________ · Ontvangen op: ____________</p>
<p>Bestelnummer: ______________________</p>
<p>Naam: ______________________</p>
<p>Adres: ______________________</p>
<p>Handtekening (alleen bij een formulier op papier): ____________</p>
<p>Datum: ____________</p>
HTML;
    }

    private static function withdrawalFr(): string
    {
        return <<<'HTML'
<p>Ne remplissez ce formulaire que si vous souhaitez vous rétracter de votre achat. Vous pouvez aussi simplement utiliser le bouton « Retourner » dans votre compte.</p>
<p>À l’attention de : Maison Anversa (entreprise individuelle de Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgique · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a></p>
<p>Je vous notifie par la présente ma rétractation du contrat portant sur la vente des biens suivants :</p>
<p>Produit et numéro : ______________________</p>
<p>Commandé le : ____________ · Reçu le : ____________</p>
<p>Numéro de commande : ______________________</p>
<p>Nom : ______________________</p>
<p>Adresse : ______________________</p>
<p>Signature (uniquement pour un formulaire papier) : ____________</p>
<p>Date : ____________</p>
HTML;
    }

    private static function privacyEn(): string
    {
        return <<<'HTML'
<h2>1. Who is responsible</h2>
<p>Maison Anversa (sole proprietorship of Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgium · company number 1019.601.147 · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. We are responsible for processing your personal data. For questions about privacy, you can reach us at that address.</p>
<h2>2. What data, for what purpose and on what basis</h2>
<table>
<thead>
<tr><th>Purpose</th><th>Data</th><th>Legal basis</th></tr>
</thead>
<tbody>
<tr><td>Your order and delivery</td><td>Name, email, phone, delivery address, order, chosen number</td><td>Performance of the contract</td></tr>
<tr><td>Payment</td><td>Payment status and method (your card details are processed only by Stripe)</td><td>Performance of the contract</td></tr>
<tr><td>Invoicing and accounting</td><td>Order and payment data</td><td>Legal obligation</td></tr>
<tr><td>Returns and guarantee</td><td>Order, messages, photos you send</td><td>Performance of the contract and legal obligation</td></tr>
<tr><td>Your account and the community</td><td>Profile, sessions, chat messages, scores, notifications</td><td>Performance of the contract</td></tr>
<tr><td>The Founding Circle Register</td><td>Number, and your name only if you choose so</td><td>Consent (you can withdraw it at any time)</td></tr>
<tr><td>Heritage Letter and waiting list</td><td>Email, first name, language, preferences</td><td>Consent (you can unsubscribe at any time)</td></tr>
<tr><td>Contact and customer service</td><td>Your messages and contact details</td><td>Legitimate interest: answering your question</td></tr>
<tr><td>Security and fraud prevention</td><td>Technical data such as IP address and log files</td><td>Legitimate interest: a secure website</td></tr>
<tr><td>Website statistics</td><td>Pages visited, type of device and browser, approximate location (country or region)</td><td>Consent via the cookie banner (you can withdraw it at any time)</td></tr>
</tbody>
</table>
<p>We never sell your data and do not use it for other companies’ advertising.</p>
<h2>3. Who receives your data</h2>
<p>We work with service providers who process your data only on our behalf:</p>
<ul>
<li><strong>Stripe</strong>: payments</li>
<li><strong>Brevo</strong>: emails and newsletter</li>
<li><strong>Carriers (such as bpost, PostNL, DPD or DHL)</strong>: delivery of your parcel</li>
<li><strong>Hostinger</strong>: website hosting, on a server in the United Kingdom</li>
<li><strong>DeepL</strong>: translation of texts on the website, and of posts and session notes you share in the community. Private messages in a session chat and your profile details are not sent to DeepL.</li>
<li><strong>Google (Google Analytics)</strong>: website statistics, only if you give your consent</li>
<li><strong>Our accountant</strong>: accounting</li>
</ul>
<p>Some of these partners (such as Stripe and Google) may process data outside the European Union. This only happens with appropriate safeguards, such as the European Commission’s standard contractual clauses. For the United Kingdom, where our server is located, the European Commission has decided that it offers an equivalent level of protection.</p>
<p>In the community, other members see what you share yourself: your profile name, your participation in sessions, your chat messages in a session and your scores.</p>
<p>To prevent misuse, Maison Anversa can read messages in a session chat and remove them where necessary. We do this only for moderation and safety.</p>
<h2>4. How long we keep your data</h2>
<table>
<thead>
<tr><th>Data</th><th>Retention period</th></tr>
</thead>
<tbody>
<tr><td>Orders and invoices</td><td>10 years (legal obligation)</td></tr>
<tr><td>Account and profile</td><td>Until you delete your account</td></tr>
<tr><td>Chat messages in sessions</td><td>30 days after the session closes or is cancelled</td></tr>
<tr><td>Entry in the register</td><td>For as long as the register exists; your name is removed as soon as you withdraw your consent</td></tr>
<tr><td>Heritage Letter</td><td>Until you unsubscribe</td></tr>
<tr><td>Waiting list</td><td>Up to 12 months after launch, unless you buy something or subscribe to the Heritage Letter</td></tr>
<tr><td>Contact messages</td><td>2 years after the last contact</td></tr>
<tr><td>Website statistics (Google Analytics)</td><td>14 months</td></tr>
<tr><td>Server logs (including IP address)</td><td>30 days</td></tr>
<tr><td>Backups</td><td>4 weeks</td></tr>
</tbody>
</table>
<h2>5. Your rights</h2>
<p>You have the right to access your data, to have it corrected or erased, to restrict processing, to object, and to receive your data in a common format. If you gave consent, you can withdraw it at any time. To do so, email <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. We reply within one month.</p>
<p>If you are not satisfied with how we handle your data, you can lodge a complaint with the Belgian Data Protection Authority, Rue de la Presse 35, 1000 Brussels (<a href="https://dataprotectionauthority.be">dataprotectionauthority.be</a>).</p>
<h2>6. Cookies</h2>
<p>A cookie is a small file that the website stores on your device. We use two kinds:</p>
<ul>
<li><strong>Necessary cookies</strong> make the website work, for example to keep you logged in, remember your language and protect forms. No consent is needed for these.</li>
<li><strong>Analytics cookies</strong> (Google Analytics) help us understand how the website is used. We only place them if you give your consent in the cookie banner.</li>
</ul>
<p>We do not use marketing or advertising cookies.</p>
<table>
<thead>
<tr><th>Cookie</th><th>Purpose</th><th>Duration</th><th>Type</th></tr>
</thead>
<tbody>
<tr><td>maison-anversa-session</td><td>Your session and login, security</td><td>Up to 2 hours of inactivity</td><td>Necessary</td></tr>
<tr><td>XSRF-TOKEN</td><td>Form security</td><td>For the length of your session</td><td>Necessary</td></tr>
<tr><td>remember_web_*</td><td>Staying logged in, only if you tick "Remember me"</td><td>About 400 days</td><td>Necessary</td></tr>
<tr><td>maison_locale</td><td>Your language (NL, EN or FR)</td><td>1 year</td><td>Necessary</td></tr>
<tr><td>maison_consent</td><td>Your choice in the cookie banner</td><td>1 year</td><td>Necessary</td></tr>
<tr><td>maison_inquiry_device</td><td>An anonymous identifier to prevent spam through the contact form</td><td>1 year</td><td>Necessary</td></tr>
<tr><td>_ga, _ga_*</td><td>Website statistics (Google Analytics)</td><td>14 months</td><td>Analytics, only with consent</td></tr>
</tbody>
</table>
<p>You can change your choice at any time through the "Cookie settings" link at the bottom of every page. You can also delete cookies in your browser settings.</p>
<p>Our fonts are hosted on our own server. We do not load external fonts, videos or advertising scripts. Instagram and WhatsApp are plain links: nothing from those services is loaded until you click them.</p>
<p>We do not track whether you open our emails or click on links in them.</p>
<h2>7. Minors</h2>
<p>An account is possible from age 16. Ordering is possible from age 18.</p>
<h2>8. Security</h2>
<p>We protect your data with appropriate technical and organisational measures, such as a secure connection (https) and restricted access.</p>
<h2>9. Changes</h2>
<p>We may change this privacy policy. The most recent version is always on this page.</p>
HTML;
    }

    private static function privacyNl(): string
    {
        return <<<'HTML'
<h2>1. Wie verantwoordelijk is</h2>
<p>Maison Anversa (eenmanszaak van Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, België · ondernemingsnummer 1019.601.147 · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. Wij zijn verantwoordelijk voor de verwerking van je persoonsgegevens. Voor vragen over privacy kun je ons op dat adres bereiken.</p>
<h2>2. Welke gegevens, waarvoor en op welke grond</h2>
<table>
<thead>
<tr><th>Waarvoor</th><th>Welke gegevens</th><th>Grond</th></tr>
</thead>
<tbody>
<tr><td>Je bestelling en levering</td><td>Naam, e-mail, telefoon, leveringsadres, bestelling, gekozen nummer</td><td>Uitvoering van de overeenkomst</td></tr>
<tr><td>Betaling</td><td>Betaalstatus en -methode (je kaartgegevens verwerkt enkel Stripe)</td><td>Uitvoering van de overeenkomst</td></tr>
<tr><td>Facturatie en boekhouding</td><td>Bestel- en betaalgegevens</td><td>Wettelijke verplichting</td></tr>
<tr><td>Retours en garantie</td><td>Bestelling, berichten, foto's die je stuurt</td><td>Uitvoering van de overeenkomst en wettelijke verplichting</td></tr>
<tr><td>Je account en de community</td><td>Profiel, sessies, chatberichten, scores, meldingen</td><td>Uitvoering van de overeenkomst</td></tr>
<tr><td>Het Founding Circle Register</td><td>Nummer, en je naam enkel als je daarvoor kiest</td><td>Toestemming (je kunt ze altijd intrekken)</td></tr>
<tr><td>Heritage Letter en wachtlijst</td><td>E-mail, voornaam, taal, voorkeuren</td><td>Toestemming (je kunt je altijd uitschrijven)</td></tr>
<tr><td>Contact en klantendienst</td><td>Je berichten en contactgegevens</td><td>Gerechtvaardigd belang: je vraag beantwoorden</td></tr>
<tr><td>Beveiliging en fraudepreventie</td><td>Technische gegevens zoals IP-adres en logbestanden</td><td>Gerechtvaardigd belang: een veilige website</td></tr>
<tr><td>Websitestatistieken</td><td>Bezochte pagina's, type apparaat en browser, geschatte locatie (land of regio)</td><td>Toestemming via de cookiebanner (je kunt ze altijd intrekken)</td></tr>
</tbody>
</table>
<p>We verkopen je gegevens nooit en gebruiken ze niet voor reclame van anderen.</p>
<h2>3. Wie je gegevens ontvangt</h2>
<p>We werken met dienstverleners die je gegevens enkel in onze opdracht verwerken:</p>
<ul>
<li><strong>Stripe</strong>: betalingen</li>
<li><strong>Brevo</strong>: e-mails en nieuwsbrief</li>
<li><strong>Vervoerders (zoals bpost, PostNL, DPD of DHL)</strong>: levering van je pakket</li>
<li><strong>Hostinger</strong>: hosting van de website, op een server in het Verenigd Koninkrijk</li>
<li><strong>DeepL</strong>: vertaling van teksten op de website, en van berichten en sessienotities die je in de community plaatst. Privéberichten in de chat van een sessie en je profielgegevens sturen we niet naar DeepL.</li>
<li><strong>Google (Google Analytics)</strong>: websitestatistieken, alleen als je daarvoor toestemming geeft</li>
<li><strong>Onze boekhouder</strong>: boekhouding</li>
</ul>
<p>Sommige van die partners (zoals Stripe en Google) kunnen gegevens buiten de Europese Unie verwerken. Dat gebeurt alleen met passende waarborgen, zoals de standaardcontractbepalingen van de Europese Commissie. Voor het Verenigd Koninkrijk, waar onze server staat, heeft de Europese Commissie bepaald dat het een gelijkwaardig beschermingsniveau biedt.</p>
<p>Andere leden zien in de community wat je zelf deelt: je profielnaam, je deelname aan sessies, je chatberichten in een sessie en je scores.</p>
<p>Om misbruik te voorkomen kan Maison Anversa de berichten in de chat van een sessie lezen en, als dat nodig is, verwijderen. We doen dat alleen voor moderatie en veiligheid.</p>
<h2>4. Hoe lang we je gegevens bewaren</h2>
<table>
<thead>
<tr><th>Gegevens</th><th>Bewaartermijn</th></tr>
</thead>
<tbody>
<tr><td>Bestellingen en facturen</td><td>10 jaar (wettelijke verplichting)</td></tr>
<tr><td>Account en profiel</td><td>Tot je je account verwijdert</td></tr>
<tr><td>Chatberichten in sessies</td><td>30 dagen na het afsluiten of annuleren van de sessie</td></tr>
<tr><td>Inschrijving in het register</td><td>Zolang het register bestaat; je naam verdwijnt zodra je je toestemming intrekt</td></tr>
<tr><td>Heritage Letter</td><td>Tot je je uitschrijft</td></tr>
<tr><td>Wachtlijst</td><td>Tot 12 maanden na de lancering, tenzij je iets koopt of je inschrijft op de Heritage Letter</td></tr>
<tr><td>Contactberichten</td><td>2 jaar na het laatste contact</td></tr>
<tr><td>Websitestatistieken (Google Analytics)</td><td>14 maanden</td></tr>
<tr><td>Serverlogs (met IP-adres)</td><td>30 dagen</td></tr>
<tr><td>Back-ups</td><td>4 weken</td></tr>
</tbody>
</table>
<h2>5. Je rechten</h2>
<p>Je hebt het recht om je gegevens in te kijken, te laten verbeteren of wissen, de verwerking te laten beperken, bezwaar te maken, en je gegevens in een gangbaar formaat te ontvangen. Gaf je toestemming, dan kun je die altijd intrekken. Mail daarvoor naar <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. We antwoorden binnen een maand.</p>
<p>Ben je niet tevreden over hoe we met je gegevens omgaan, dan kun je een klacht indienen bij de Gegevensbeschermingsautoriteit, Drukpersstraat 35, 1000 Brussel (<a href="https://gegevensbeschermingsautoriteit.be">gegevensbeschermingsautoriteit.be</a>).</p>
<h2>6. Cookies</h2>
<p>Een cookie is een klein bestand dat de website op je toestel bewaart. We gebruiken twee soorten:</p>
<ul>
<li><strong>Noodzakelijke cookies</strong> laten de website werken, bijvoorbeeld om je ingelogd te houden, je taalkeuze te onthouden en formulieren te beveiligen. Daarvoor is geen toestemming nodig.</li>
<li><strong>Analytische cookies</strong> (Google Analytics) helpen ons te begrijpen hoe de website gebruikt wordt. Die plaatsen we alleen als je daarvoor toestemming geeft in de cookiebanner.</li>
</ul>
<p>We gebruiken geen marketing- of advertentiecookies.</p>
<table>
<thead>
<tr><th>Cookie</th><th>Waarvoor</th><th>Bewaartermijn</th><th>Soort</th></tr>
</thead>
<tbody>
<tr><td>maison-anversa-session</td><td>Je sessie en login, beveiliging</td><td>Tot 2 uur zonder activiteit</td><td>Noodzakelijk</td></tr>
<tr><td>XSRF-TOKEN</td><td>Beveiliging van formulieren</td><td>Zolang je sessie loopt</td><td>Noodzakelijk</td></tr>
<tr><td>remember_web_*</td><td>Ingelogd blijven, alleen als je "Onthoud mij" aanvinkt</td><td>Ongeveer 400 dagen</td><td>Noodzakelijk</td></tr>
<tr><td>maison_locale</td><td>Je taalkeuze (NL, EN of FR)</td><td>1 jaar</td><td>Noodzakelijk</td></tr>
<tr><td>maison_consent</td><td>Je keuze in de cookiebanner</td><td>1 jaar</td><td>Noodzakelijk</td></tr>
<tr><td>maison_inquiry_device</td><td>Een anoniem kenmerk om spam via het contactformulier te voorkomen</td><td>1 jaar</td><td>Noodzakelijk</td></tr>
<tr><td>_ga, _ga_*</td><td>Websitestatistieken (Google Analytics)</td><td>14 maanden</td><td>Analytisch, alleen met toestemming</td></tr>
</tbody>
</table>
<p>Je kunt je keuze altijd wijzigen via de link "Cookie-instellingen" onderaan elke pagina. Je kunt cookies ook verwijderen in de instellingen van je browser.</p>
<p>Onze lettertypes staan op onze eigen server. We laden geen externe lettertypes, video's of advertentiescripts. Instagram en WhatsApp zijn gewone links: er wordt niets van die diensten geladen tot je erop klikt.</p>
<p>In onze e-mails meten we niet of je ze opent of op links klikt.</p>
<h2>7. Minderjarigen</h2>
<p>Een account kan vanaf 16 jaar. Bestellen kan vanaf 18 jaar.</p>
<h2>8. Beveiliging</h2>
<p>We beschermen je gegevens met passende technische en organisatorische maatregelen, zoals een beveiligde verbinding (https) en beperkte toegang.</p>
<h2>9. Wijzigingen</h2>
<p>We kunnen dit privacybeleid aanpassen. De meest recente versie staat altijd op deze pagina.</p>
HTML;
    }

    private static function privacyFr(): string
    {
        return <<<'HTML'
<h2>1. Responsable du traitement</h2>
<p>Maison Anversa (entreprise individuelle de Yusuf Savran), Graaf de Broquevillestraat 29 bus 301, 2400 Mol, Belgique · numéro d’entreprise 1019.601.147 · <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. Nous sommes responsables du traitement de vos données personnelles. Pour toute question relative à la vie privée, vous pouvez nous joindre à cette adresse.</p>
<h2>2. Quelles données, pourquoi et sur quelle base</h2>
<table>
<thead>
<tr><th>Finalité</th><th>Données</th><th>Base légale</th></tr>
</thead>
<tbody>
<tr><td>Votre commande et la livraison</td><td>Nom, e-mail, téléphone, adresse de livraison, commande, numéro choisi</td><td>Exécution du contrat</td></tr>
<tr><td>Paiement</td><td>Statut et moyen de paiement (vos données de carte sont traitées uniquement par Stripe)</td><td>Exécution du contrat</td></tr>
<tr><td>Facturation et comptabilité</td><td>Données de commande et de paiement</td><td>Obligation légale</td></tr>
<tr><td>Retours et garantie</td><td>Commande, messages, photos que vous envoyez</td><td>Exécution du contrat et obligation légale</td></tr>
<tr><td>Votre compte et la communauté</td><td>Profil, sessions, messages de chat, scores, notifications</td><td>Exécution du contrat</td></tr>
<tr><td>Le Founding Circle Register</td><td>Numéro, et votre nom uniquement si vous le choisissez</td><td>Consentement (vous pouvez le retirer à tout moment)</td></tr>
<tr><td>Heritage Letter et liste d’attente</td><td>E-mail, prénom, langue, préférences</td><td>Consentement (vous pouvez vous désinscrire à tout moment)</td></tr>
<tr><td>Contact et service client</td><td>Vos messages et coordonnées</td><td>Intérêt légitime : répondre à votre question</td></tr>
<tr><td>Sécurité et prévention de la fraude</td><td>Données techniques comme l’adresse IP et les fichiers journaux</td><td>Intérêt légitime : un site sécurisé</td></tr>
<tr><td>Statistiques du site</td><td>Pages consultées, type d’appareil et de navigateur, localisation approximative (pays ou région)</td><td>Consentement via la bannière cookies (vous pouvez le retirer à tout moment)</td></tr>
</tbody>
</table>
<p>Nous ne vendons jamais vos données et ne les utilisons pas pour la publicité de tiers.</p>
<h2>3. Qui reçoit vos données</h2>
<p>Nous travaillons avec des prestataires qui traitent vos données uniquement pour notre compte :</p>
<ul>
<li><strong>Stripe</strong> : paiements</li>
<li><strong>Brevo</strong> : e-mails et newsletter</li>
<li><strong>Transporteurs (comme bpost, PostNL, DPD ou DHL)</strong> : livraison de votre colis</li>
<li><strong>Hostinger</strong> : hébergement du site, sur un serveur situé au Royaume-Uni</li>
<li><strong>DeepL</strong> : traduction des textes du site, ainsi que des messages et notes de session que vous publiez dans la communauté. Les messages privés de la discussion d’une session et les données de votre profil ne sont pas envoyés à DeepL.</li>
<li><strong>Google (Google Analytics)</strong> : statistiques du site, uniquement si vous y consentez</li>
<li><strong>Notre comptable</strong> : comptabilité</li>
</ul>
<p>Certains de ces partenaires (comme Stripe et Google) peuvent traiter des données en dehors de l’Union européenne. Cela se fait uniquement avec des garanties appropriées, comme les clauses contractuelles types de la Commission européenne. Pour le Royaume-Uni, où se trouve notre serveur, la Commission européenne a décidé qu’il offre un niveau de protection équivalent.</p>
<p>Dans la communauté, les autres membres voient ce que vous partagez vous-même : votre nom de profil, votre participation aux sessions, vos messages de chat dans une session et vos scores.</p>
<p>Afin d’éviter tout abus, Maison Anversa peut lire les messages de la discussion d’une session et les supprimer si nécessaire. Nous le faisons uniquement à des fins de modération et de sécurité.</p>
<h2>4. Durée de conservation</h2>
<table>
<thead>
<tr><th>Données</th><th>Durée de conservation</th></tr>
</thead>
<tbody>
<tr><td>Commandes et factures</td><td>10 ans (obligation légale)</td></tr>
<tr><td>Compte et profil</td><td>Jusqu’à la suppression de votre compte</td></tr>
<tr><td>Messages de chat dans les sessions</td><td>30 jours après la clôture ou l’annulation de la session</td></tr>
<tr><td>Inscription au registre</td><td>Tant que le registre existe ; votre nom est retiré dès que vous retirez votre consentement</td></tr>
<tr><td>Heritage Letter</td><td>Jusqu’à votre désinscription</td></tr>
<tr><td>Liste d’attente</td><td>Jusqu’à 12 mois après le lancement, sauf si vous achetez ou vous inscrivez à la Heritage Letter</td></tr>
<tr><td>Messages de contact</td><td>2 ans après le dernier contact</td></tr>
<tr><td>Statistiques du site (Google Analytics)</td><td>14 mois</td></tr>
<tr><td>Journaux du serveur (avec adresse IP)</td><td>30 jours</td></tr>
<tr><td>Sauvegardes</td><td>4 semaines</td></tr>
</tbody>
</table>
<h2>5. Vos droits</h2>
<p>Vous avez le droit d’accéder à vos données, de les faire rectifier ou effacer, de limiter le traitement, de vous y opposer et de recevoir vos données dans un format courant. Si vous avez donné votre consentement, vous pouvez le retirer à tout moment. Écrivez pour cela à <a href="mailto:hello@maisonanversa.com">hello@maisonanversa.com</a>. Nous répondons dans un délai d’un mois.</p>
<p>Si vous n’êtes pas satisfait de la manière dont nous traitons vos données, vous pouvez introduire une plainte auprès de l’Autorité de protection des données, rue de la Presse 35, 1000 Bruxelles (<a href="https://autoriteprotectiondonnees.be">autoriteprotectiondonnees.be</a>).</p>
<h2>6. Cookies</h2>
<p>Un cookie est un petit fichier que le site enregistre sur votre appareil. Nous en utilisons deux sortes :</p>
<ul>
<li><strong>Les cookies nécessaires</strong> font fonctionner le site, par exemple pour vous garder connecté, retenir votre langue et sécuriser les formulaires. Aucun consentement n’est requis pour ceux-ci.</li>
<li><strong>Les cookies analytiques</strong> (Google Analytics) nous aident à comprendre comment le site est utilisé. Nous ne les plaçons que si vous y consentez dans la bannière cookies.</li>
</ul>
<p>Nous n’utilisons pas de cookies marketing ou publicitaires.</p>
<table>
<thead>
<tr><th>Cookie</th><th>Finalité</th><th>Durée</th><th>Type</th></tr>
</thead>
<tbody>
<tr><td>maison-anversa-session</td><td>Votre session et connexion, sécurité</td><td>Jusqu’à 2 heures d’inactivité</td><td>Nécessaire</td></tr>
<tr><td>XSRF-TOKEN</td><td>Sécurité des formulaires</td><td>Pendant votre session</td><td>Nécessaire</td></tr>
<tr><td>remember_web_*</td><td>Rester connecté, uniquement si vous cochez « Se souvenir de moi »</td><td>Environ 400 jours</td><td>Nécessaire</td></tr>
<tr><td>maison_locale</td><td>Votre langue (NL, EN ou FR)</td><td>1 an</td><td>Nécessaire</td></tr>
<tr><td>maison_consent</td><td>Votre choix dans la bannière cookies</td><td>1 an</td><td>Nécessaire</td></tr>
<tr><td>maison_inquiry_device</td><td>Un identifiant anonyme pour prévenir le spam via le formulaire de contact</td><td>1 an</td><td>Nécessaire</td></tr>
<tr><td>_ga, _ga_*</td><td>Statistiques du site (Google Analytics)</td><td>14 mois</td><td>Analytique, uniquement avec consentement</td></tr>
</tbody>
</table>
<p>Vous pouvez modifier votre choix à tout moment via le lien « Paramètres des cookies » en bas de chaque page. Vous pouvez aussi supprimer les cookies dans les réglages de votre navigateur.</p>
<p>Nos polices sont hébergées sur notre propre serveur. Nous ne chargeons pas de polices, de vidéos ou de scripts publicitaires externes. Instagram et WhatsApp sont de simples liens : rien de ces services n’est chargé tant que vous ne cliquez pas dessus.</p>
<p>Nous ne mesurons pas si vous ouvrez nos e-mails ou si vous cliquez sur leurs liens.</p>
<h2>7. Mineurs</h2>
<p>Un compte est possible à partir de 16 ans. Commander est possible à partir de 18 ans.</p>
<h2>8. Sécurité</h2>
<p>Nous protégeons vos données par des mesures techniques et organisationnelles appropriées, comme une connexion sécurisée (https) et un accès restreint.</p>
<h2>9. Modifications</h2>
<p>Nous pouvons modifier cette politique de confidentialité. La version la plus récente se trouve toujours sur cette page.</p>
HTML;
    }
}
