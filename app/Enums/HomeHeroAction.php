<?php

namespace App\Enums;

enum HomeHeroAction: string
{
    case FoundingProduct = 'founding_product';
    case FoundingCircle = 'founding_circle';
    case RegisterFoundingCircle = 'register_founding_circle';
    case Community = 'community';
    case ClubCorner = 'club_corner';
    case Journal = 'journal';
    case MaisonPage = 'maison_page';
    case Newsletter = 'newsletter';
    case External = 'external';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::FoundingProduct => 'Heritage No.001',
            self::FoundingCircle => 'Founding Circle',
            self::RegisterFoundingCircle => 'Founding Circle registreren',
            self::Community => 'Gemeenschap',
            self::ClubCorner => 'Club Corner',
            self::Journal => 'Journal',
            self::MaisonPage => 'Maison-pagina',
            self::Newsletter => 'Heritage Letter',
            self::External => 'Externe link',
            self::Hidden => 'Verborgen',
        };
    }

    public function needsTarget(): bool
    {
        return $this === self::MaisonPage || $this === self::External;
    }
}
