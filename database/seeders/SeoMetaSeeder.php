<?php

namespace Database\Seeders;

use App\Models\SeoMeta;
use App\Support\Seo\MaisonSeo;
use Illuminate\Database\Seeder;

class SeoMetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MaisonSeo::defaults() as $pageKey => $copy) {
            SeoMeta::query()->updateOrCreate(
                ['page_key' => $pageKey],
                [
                    'title' => $copy['title'],
                    'description' => $copy['description'],
                ],
            );
        }
    }
}
