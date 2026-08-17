<?php

namespace Database\Seeders;

use App\Services\Journal\JournalStaticImporter;
use Illuminate\Database\Seeder;

class JournalArticleSeeder extends Seeder
{
    public function run(): void
    {
        app(JournalStaticImporter::class)->import();
    }
}
