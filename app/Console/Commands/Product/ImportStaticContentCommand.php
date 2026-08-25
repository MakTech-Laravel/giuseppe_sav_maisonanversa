<?php

namespace App\Console\Commands\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Database\Seeders\FaqSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:import-static-content-command')]
#[Description('Import static Maison content into database')]
class ImportStaticContentCommand extends Command
{
    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => ProductSeeder::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => FaqSeeder::class, '--force' => true]);

        foreach ($this->upcomingProductRows() as $slug => $attributes) {
            Product::query()->updateOrCreate(['slug' => $slug], $attributes);
        }

        $this->info('Imported static content for the Maison catalog.');

        return self::SUCCESS;
    }

    /**
     * Placeholder chapters announced on the catalog but not yet for sale.
     *
     * @return array<string, array<string, mixed>>
     */
    private function upcomingProductRows(): array
    {
        return [
            'heritage-no-002' => [
                'name' => 'Heritage No.002',
                'type' => ProductType::LimitedEdition,
                'status' => ProductStatus::ComingSoon,
                'amount' => '0.00',
                'currency' => 'eur',
                'edition_total' => 0,
                'archive_edition_numbers' => [],
                'is_published' => true,
                'grants_founding_circle' => false,
                'sort_order' => 1,
                'gallery' => ['heritage-001-front'],
                'eyebrow' => 'Maison Anversa · Coming Soon',
                'hero_eyebrow' => 'Volgende release',
                'hero_subtitle' => 'Volgende release · Aankondiging via de Heritage Letter',
                'description' => '',
            ],
            'heritage-no-003' => [
                'name' => 'Heritage No.003',
                'type' => ProductType::LimitedEdition,
                'status' => ProductStatus::ComingSoon,
                'amount' => '0.00',
                'currency' => 'eur',
                'edition_total' => 0,
                'archive_edition_numbers' => [],
                'is_published' => true,
                'grants_founding_circle' => false,
                'sort_order' => 2,
                'gallery' => ['heritage-001-lifestyle-court'],
                'eyebrow' => 'Maison Anversa · Coming Soon',
                'hero_eyebrow' => 'In voorbereiding',
                'hero_subtitle' => 'In voorbereiding · Geen datum bekend',
                'description' => '',
            ],
        ];
    }
}
