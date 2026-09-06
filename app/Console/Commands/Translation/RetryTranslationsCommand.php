<?php

namespace App\Console\Commands\Translation;

use App\Jobs\TranslateModelJob;
use App\Models\Club;
use App\Models\CommerceSetting;
use App\Models\CommunityComment;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\Faq;
use App\Models\JournalArticle;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('translation:retry {model?} {id?} {--sync : Run translation jobs immediately}')]
#[Description('Queue DeepL translations for opted-in models')]
class RetryTranslationsCommand extends Command
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const MODELS = [
        'communitypost' => CommunityPost::class,
        'communitycomment' => CommunityComment::class,
        'club' => Club::class,
        'communityevent' => CommunityEvent::class,
        'communitysession' => CommunitySession::class,
        'journalarticle' => JournalArticle::class,
        'product' => Product::class,
        'faq' => Faq::class,
        'commercesetting' => CommerceSetting::class,
    ];

    public function handle(): int
    {
        $models = $this->resolveModels();

        if ($models === []) {
            $this->error('Unknown model. Use communitypost, communitycomment, club, communityevent, communitysession, journalarticle, product, or commercesetting.');

            return self::FAILURE;
        }

        $id = $this->argument('id');
        $dispatched = 0;

        foreach ($models as $modelClass) {
            $query = $modelClass::query()->orderBy('id');

            if (filled($id)) {
                $query->whereKey($id);
            }

            $query->chunkById(100, function ($records) use (&$dispatched): void {
                foreach ($records as $record) {
                    if ($this->option('sync')) {
                        TranslateModelJob::dispatchSync($record::class, (int) $record->getKey());
                    } else {
                        TranslateModelJob::dispatch($record::class, (int) $record->getKey());
                    }

                    $dispatched++;
                }
            });
        }

        $this->info("Dispatched {$dispatched} translation job(s).");

        return self::SUCCESS;
    }

    /**
     * @return list<class-string<Model>>
     */
    private function resolveModels(): array
    {
        $model = $this->argument('model');

        if (blank($model)) {
            return array_values(array_unique(array_values(self::MODELS)));
        }

        $key = strtolower((string) str_replace(['\\', '_', '-'], '', (string) $model));
        $key = str_replace('appmodels', '', $key);

        if (isset(self::MODELS[$key])) {
            return [self::MODELS[$key]];
        }

        $basename = strtolower(class_basename((string) $model));

        if (isset(self::MODELS[$basename])) {
            return [self::MODELS[$basename]];
        }

        return [];
    }
}
