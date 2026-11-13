<?php

declare(strict_types=1);

$en = [
    'Guest order?' => 'Guest order?',
    'Ordered without an account? Enter your order number and email. We will send you a one-time link to start a return.' => 'Ordered without an account? Enter your order number and email. We will send you a one-time link to start a return.',
    'Order number' => 'Order number',
    'Email used for the order' => 'Email used for the order',
    'Send return link' => 'Send return link',
    'Create an account to join' => 'Create an account to join',
];

$fr = [
    'Guest order?' => 'Commande invité ?',
    'Ordered without an account? Enter your order number and email. We will send you a one-time link to start a return.' => 'Commandé sans compte ? Indiquez votre numéro de commande et votre e-mail. Nous vous enverrons un lien unique pour démarrer un retour.',
    'Order number' => 'Numéro de commande',
    'Email used for the order' => 'E-mail utilisé pour la commande',
    'Send return link' => 'Envoyer le lien de retour',
    'Create an account to join' => 'Créez un compte pour participer',
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
