<?php

namespace App\Support;

trait BrandsMaisonMail
{
    /**
     * @return array{logoUrl: string, homeUrl?: string}
     */
    protected function maisonBrandData(?string $locale = null, bool $withHomeCta = true): array
    {
        $data = [
            'logoUrl' => asset('images/logos/logo-icon.jpg'),
        ];

        if ($withHomeCta) {
            $data['homeUrl'] = route('maison.home', [
                'locale' => MailLocale::resolve($locale),
            ]);
        }

        return $data;
    }
}
