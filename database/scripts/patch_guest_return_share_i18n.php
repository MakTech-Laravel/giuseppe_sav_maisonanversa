<?php

declare(strict_types=1);

$en = [
    'Gastbestelling?' => 'Guest order?',
    'Heb je zonder account besteld? Vul je bestelnummer en e-mail in. We sturen je een eenmalige link om een retour te starten.' => 'Ordered without an account? Enter your order number and email. We will send you a one-time link to start a return.',
    'Bestelnummer' => 'Order number',
    'E-mail bij de bestelling' => 'Email used for the order',
    'Stuur retourlink' => 'Send return link',
    'Maak een account om mee te doen' => 'Create an account to join',
];

$fr = [
    'Gastbestelling?' => 'Commande invité ?',
    'Heb je zonder account besteld? Vul je bestelnummer en e-mail in. We sturen je een eenmalige link om een retour te starten.' => 'Commandé sans compte ? Indiquez votre numéro de commande et votre e-mail. Nous vous enverrons un lien unique pour démarrer un retour.',
    'Bestelnummer' => 'Numéro de commande',
    'E-mail bij de bestelling' => 'E-mail utilisé pour la commande',
    'Stuur retourlink' => 'Envoyer le lien de retour',
    'Maak een account om mee te doen' => 'Créez un compte pour participer',
];

function mergeLocale(string $path, array $patch): void
{
    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    foreach ($patch as $key => $value) {
        $data[$key] = $value;
    }

    ksort($data, SORT_STRING);

    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    );

    echo basename($path).' patched '.count($patch)." keys\n";
}

$root = dirname(__DIR__, 2);
mergeLocale($root.'/lang/en.json', $en);
mergeLocale($root.'/lang/fr.json', $fr);
