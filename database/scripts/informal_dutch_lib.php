<?php

declare(strict_types=1);

/**
 * Convert a Dutch UI string from formal u/uw to informal je/jouw.
 */
function toInformalDutch(string $text): string
{
    $placeholders = [];
    $text = preg_replace_callback('/a\.u\.b\./iu', function (array $m) use (&$placeholders): string {
        $key = '<<<AUB'.count($placeholders).'>>>';
        $placeholders[$key] = $m[0];

        return $key;
    }, $text) ?? $text;

    $pairs = [
        '/\bDank u\b/u' => 'Dank je',
        '/\bdank u\b/u' => 'dank je',
        '/\bSchrijf u in\b/u' => 'Schrijf je in',
        '/\bschrijf u in\b/u' => 'schrijf je in',
        '/\bMeld u aan\b/u' => 'Meld je aan',
        '/\bmeld u aan\b/u' => 'meld je aan',
        '/\bSluit u aan\b/u' => 'Sluit je aan',
        '/\bsluit u aan\b/u' => 'sluit je aan',
        '/\bDoor u in te schrijven gaat u\b/u' => 'Door je in te schrijven ga je',
        '/\bDoor een sessie aan te maken gaat u\b/u' => 'Door een sessie aan te maken ga je',
        '/\bgaat u akkoord\b/u' => 'ga je akkoord',
        '/\bGaat u akkoord\b/u' => 'Ga je akkoord',

        '/\bU heeft\b/u' => 'Je hebt',
        '/\bU hebt\b/u' => 'Je hebt',
        '/\bU bent\b/u' => 'Je bent',
        '/\bU wordt\b/u' => 'Je wordt',
        '/\bU kunt\b/u' => 'Je kunt',
        '/\bU wilt\b/u' => 'Je wilt',
        '/\bU mag\b/u' => 'Je mag',
        '/\bU moet\b/u' => 'Je moet',
        '/\bU ziet\b/u' => 'Je ziet',
        '/\bU vindt\b/u' => 'Je vindt',
        '/\bU speelt\b/u' => 'Je speelt',
        '/\bU betaalt\b/u' => 'Je betaalt',
        '/\bU ontvangt\b/u' => 'Je ontvangt',
        '/\bU kiest\b/u' => 'Je kiest',
        '/\bU neemt\b/u' => 'Je neemt',
        '/\bU bewerkt\b/u' => 'Je bewerkt',
        '/\bU krijgt\b/u' => 'Je krijgt',
        '/\bU gebruikt\b/u' => 'Je gebruikt',
        '/\bU houdt\b/u' => 'Je houdt',
        '/\bU blijft\b/u' => 'Je blijft',
        '/\bU staat\b/u' => 'Je staat',
        '/\bU gaat\b/u' => 'Je gaat',
        '/\bU doet\b/u' => 'Je doet',
        '/\bU hoeft\b/u' => 'Je hoeft',
        '/\bU koopt\b/u' => 'Je koopt',

        '/\bHeeft u\b/u' => 'Heb je',
        '/\bHebt u\b/u' => 'Heb je',
        '/\bBent u\b/u' => 'Ben je',
        '/\bWordt u\b/u' => 'Word je',
        '/\bKunt u\b/u' => 'Kun je',
        '/\bWilt u\b/u' => 'Wil je',
        '/\bMoet u\b/u' => 'Moet je',
        '/\bMag u\b/u' => 'Mag je',
        '/\bHoeft u\b/u' => 'Hoef je',
        '/\bVindt u\b/u' => 'Vind je',
        '/\bZiet u\b/u' => 'Zie je',
        '/\bOntvangt u\b/u' => 'Ontvang je',
        '/\bKiest u\b/u' => 'Kies je',
        '/\bStuurt u\b/u' => 'Stuur je',
        '/\bVolgt u\b/u' => 'Volg je',
        '/\bRegelt u\b/u' => 'Regel je',
        '/\bGaat u\b/u' => 'Ga je',

        '/\bhoeft u\b/u' => 'hoef je',
        '/\bkunt u\b/u' => 'kun je',
        '/\bwilt u\b/u' => 'wil je',
        '/\bwordt u\b/u' => 'word je',
        '/\bbent u\b/u' => 'ben je',
        '/\bvindt u\b/u' => 'vind je',
        '/\bontvangt u\b/u' => 'ontvang je',
        '/\bkiest u\b/u' => 'kies je',
        '/\bstuurt u\b/u' => 'stuur je',
        '/\bvolgt u\b/u' => 'volg je',
        '/\bregelt u\b/u' => 'regel je',
        '/\bgaat u\b/u' => 'ga je',

        '/\bAls u\b/u' => 'Als je',
        '/\bals u\b/u' => 'als je',
        '/\bZodra u\b/u' => 'Zodra je',
        '/\bzodra u\b/u' => 'zodra je',
        '/\bWanneer u\b/u' => 'Wanneer je',
        '/\bwanneer u\b/u' => 'wanneer je',
        '/\bNadat u\b/u' => 'Nadat je',
        '/\bnadat u\b/u' => 'nadat je',
        '/\bVoordat u\b/u' => 'Voordat je',
        '/\bvoordat u\b/u' => 'voordat je',
        '/\bTerwijl u\b/u' => 'Terwijl je',
        '/\bterwijl u\b/u' => 'terwijl je',
        '/\bOmdat u\b/u' => 'Omdat je',
        '/\bomdat u\b/u' => 'omdat je',
        '/\bDat u\b/u' => 'Dat je',
        '/\bdat u\b/u' => 'dat je',
        '/\bOf u\b/u' => 'Of je',
        '/\bof u\b/u' => 'of je',
        '/\bHoe u\b/u' => 'Hoe je',
        '/\bhoe u\b/u' => 'hoe je',
        '/\bWat u\b/u' => 'Wat je',
        '/\bwat u\b/u' => 'wat je',
        '/\bWaar u\b/u' => 'Waar je',
        '/\bwaar u\b/u' => 'waar je',
        '/\bDie u\b/u' => 'Die je',
        '/\bdie u\b/u' => 'die je',
        '/\bWaarmee u\b/u' => 'Waarmee je',
        '/\bwaarmee u\b/u' => 'waarmee je',
        '/\bWaarin u\b/u' => 'Waarin je',
        '/\bwaarin u\b/u' => 'waarin je',
        '/\bWaarvan u\b/u' => 'Waarvan je',
        '/\bwaarvan u\b/u' => 'waarvan je',

        '/\bmet u\b/u' => 'met je',
        '/\bMet u\b/u' => 'Met je',
        '/\bvoor u\b/u' => 'voor je',
        '/\bVoor u\b/u' => 'Voor je',
        '/\bnaar u\b/u' => 'naar je',
        '/\bNaar u\b/u' => 'Naar je',
        '/\bvan u\b/u' => 'van je',
        '/\bVan u\b/u' => 'Van je',
        '/\bbij u\b/u' => 'bij je',
        '/\bBij u\b/u' => 'Bij je',
        '/\bover u\b/u' => 'over je',
        '/\bOver u\b/u' => 'Over je',
        '/\bop u\b/u' => 'op je',
        '/\bOp u\b/u' => 'Op je',
        '/\bin u\b/u' => 'in je',
        '/\bIn u\b/u' => 'In je',
        '/\buit u\b/u' => 'uit je',
        '/\bUit u\b/u' => 'Uit je',
        '/\baan u\b/u' => 'aan je',
        '/\bAan u\b/u' => 'Aan je',
        '/\btegen u\b/u' => 'tegen je',
        '/\bTegen u\b/u' => 'Tegen je',
        '/\ben u\b/u' => 'en je',
        '/\bEn u\b/u' => 'En je',
        '/\bwij u\b/u' => 'wij je',
        '/\bWij u\b/u' => 'Wij je',
        '/\bbrengt u\b/u' => 'brengt je',
        '/\bBrengt u\b/u' => 'Brengt je',
        '/\badviseren u\b/u' => 'adviseren je',
        '/\bnodigen wij u\b/u' => 'nodigen wij je',
        '/\bsturen u\b/u' => 'sturen je',
        '/\bzenden u\b/u' => 'zenden je',
        '/\bvragen u\b/u' => 'vragen je',
    ];

    foreach ($pairs as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text) ?? $text;
    }

    $text = preg_replace('/\bUw\b/u', 'Jouw', $text) ?? $text;
    $text = preg_replace('/\buw\b/u', 'jouw', $text) ?? $text;
    $text = preg_replace('/\bU\b/u', 'Je', $text) ?? $text;
    $text = preg_replace('/\bu\b/u', 'je', $text) ?? $text;

    $agreement = [
        '/\bje heeft\b/u' => 'je hebt',
        '/\bJe heeft\b/u' => 'Je hebt',
        '/\bhoeft je\b/u' => 'hoef je',
        '/\bHoeft je\b/u' => 'Hoef je',
        '/\bAls je geen account heeft\b/u' => 'Als je geen account hebt',
        '/\bAls je geen wachtwoordreset heeft\b/u' => 'Als je geen wachtwoordreset hebt',
        '/\bis van je\b/u' => 'is van jou',
        '/\bvan je —\b/u' => 'van jou —',
    ];

    foreach ($agreement as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text) ?? $text;
    }

    foreach ($placeholders as $key => $original) {
        $text = str_replace($key, $original, $text);
    }

    return $text;
}

function shouldSkipInformalKey(string $key): bool
{
    return str_contains($key, '"U": de klant');
}

function isFormalDutchAddress(string $text): bool
{
    $probe = preg_replace('/a\.u\.b\./iu', '', $text) ?? $text;

    return (bool) (
        preg_match('/\buw\b|\bUw\b|\bUW\b/u', $probe)
        || preg_match('/\b[Uu]\b(?!\.)/u', $probe)
    );
}
