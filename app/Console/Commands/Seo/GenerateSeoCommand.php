<?php

namespace App\Console\Commands\Seo;

use App\Support\Seo\MaisonSitemapBuilder;
use App\Support\Seo\RobotsTxtGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSeoCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'seo:generate
        {--write-files : Write sitemap.xml and robots.txt to storage/app/seo/ for ops inspection}';

    /**
     * @var string
     */
    protected $description = 'Warm the sitemap cache and optionally write SEO files to disk';

    public function handle(MaisonSitemapBuilder $builder, RobotsTxtGenerator $robotsGenerator): int
    {
        $xml = $builder->warm();
        $this->info('Sitemap cache warmed ('.number_format(strlen($xml)).' bytes).');

        if ($this->option('write-files')) {
            $dir = storage_path('app/seo');
            File::ensureDirectoryExists($dir);

            File::put($dir.'/sitemap.xml', $xml);
            $this->info('Written: storage/app/seo/sitemap.xml');

            $result = $robotsGenerator->generate();
            File::put($dir.'/robots.txt', $result['body']);
            $this->info('Written: storage/app/seo/robots.txt');
        }

        return self::SUCCESS;
    }
}
