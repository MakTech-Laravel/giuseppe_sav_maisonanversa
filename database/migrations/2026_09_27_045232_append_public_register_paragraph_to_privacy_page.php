<?php

use App\Models\LegalPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MARKER = 'Publiek register';

    /**
     * Append the public-register paragraph without replacing the rest of the privacy body.
     */
    public function up(): void
    {
        $page = DB::table('legal_pages')->where('slug', 'privacy')->first();

        if ($page === null || str_contains((string) $page->body, self::MARKER)) {
            return;
        }

        $paragraph = <<<'HTML'
<h2>Publiek register</h2>
<p>Als u Heritage No.001 koopt, wordt u ingeschreven in het officiële Founding Circle-register. Standaard verschijnt alleen uw nummer als Privélid. U kiest in uw account of uw volledige naam, uw voornaam en initiaal, of alleen uw nummer publiek zichtbaar is. U kunt die keuze op elk moment wijzigen of intrekken.</p>
HTML;

        $body = rtrim((string) $page->body)."\n".$paragraph;

        DB::table('legal_pages')->where('id', $page->id)->update([
            'body' => $body,
            'updated_at' => now(),
        ]);

        $hash = hash('sha256', $body);

        $additions = [
            'en' => <<<'HTML'
<h2>Public register</h2>
<p>When you buy Heritage No.001, you are inscribed in the official Founding Circle register. By default only your number appears as a private member. In your account you choose whether your full name, your first name and initial, or only your number is public. You can change or withdraw that choice at any time.</p>
HTML,
            'fr' => <<<'HTML'
<h2>Registre public</h2>
<p>Lorsque vous achetez Heritage No.001, vous êtes inscrit au registre officiel du Founding Circle. Par défaut, seul votre numéro apparaît comme membre privé. Dans votre compte, vous choisissez si votre nom complet, votre prénom et initiale, ou seulement votre numéro est public. Vous pouvez modifier ou retirer ce choix à tout moment.</p>
HTML,
        ];

        foreach ($additions as $locale => $addition) {
            $translation = DB::table('translations')
                ->where('translatable_type', LegalPage::class)
                ->where('translatable_id', $page->id)
                ->where('locale', $locale)
                ->where('column', 'body')
                ->first();

            $marker = $locale === 'en' ? 'Public register' : 'Registre public';

            if ($translation === null || str_contains((string) $translation->value, $marker)) {
                if ($translation !== null) {
                    DB::table('translations')->where('id', $translation->id)->update([
                        'source_hash' => $hash,
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            DB::table('translations')->where('id', $translation->id)->update([
                'value' => rtrim((string) $translation->value)."\n".$addition,
                'source_hash' => $hash,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * The previous privacy wording is not restored.
     */
    public function down(): void
    {
        //
    }
};
