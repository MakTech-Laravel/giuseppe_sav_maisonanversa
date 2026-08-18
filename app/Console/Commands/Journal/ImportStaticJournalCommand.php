<?php

namespace App\Console\Commands\Journal;

use App\Services\Journal\JournalStaticImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('journal:import-static {--fresh : Delete existing journal articles before import}')]
#[Description('Import the static Journal catalog into journal_articles')]
class ImportStaticJournalCommand extends Command
{
    public function handle(JournalStaticImporter $importer): int
    {
        $count = $importer->import(fresh: (bool) $this->option('fresh'));

        $this->info("Imported {$count} journal article(s).");

        return self::SUCCESS;
    }
}
