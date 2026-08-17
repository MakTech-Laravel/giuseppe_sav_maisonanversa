<?php

namespace App\Services\Journal;

use App\Models\JournalArticle;
use App\Models\Translation;
use App\Support\Journal;
use Illuminate\Support\Facades\DB;

class JournalStaticImporter
{
    /**
     * Import the static Journal catalog into journal_articles (+ translations).
     *
     * @return int Number of articles upserted
     */
    public function import(bool $fresh = false): int
    {
        if ($fresh) {
            JournalArticle::query()->delete();
        }

        $count = 0;

        DB::transaction(function () use (&$count): void {
            foreach (Journal::staticCatalog() as $index => $article) {
                $body = is_array($article['body'][0] ?? null)
                    ? Journal::joinBodyParagraphs($article['body'])
                    : $article['body'];

                JournalArticle::withoutEvents(function () use ($article, $body, $index, &$count): void {
                    $model = JournalArticle::query()->updateOrCreate(
                        ['slug' => $article['slug']],
                        [
                            'title' => $article['title']['nl'],
                            'excerpt' => $article['excerpt']['nl'],
                            'body' => $body['nl'],
                            'cover_path' => $article['asset'],
                            'category' => $article['category']['nl'],
                            'author' => $article['author'],
                            'date_label' => $article['date']['nl'],
                            'published_at' => now()->subYears(2)->addDays($index),
                            'sort_order' => $index,
                        ],
                    );

                    $this->syncLocale($model, 'en', [
                        'title' => $article['title']['en'],
                        'excerpt' => $article['excerpt']['en'],
                        'body' => $body['en'],
                        'category' => $article['category']['en'],
                        'date_label' => $article['date']['en'],
                    ]);

                    $this->syncLocale($model, 'fr', [
                        'title' => $article['title']['fr'],
                        'excerpt' => $article['excerpt']['fr'],
                        'body' => $body['fr'],
                        'category' => $article['category']['fr'],
                        'date_label' => $article['date']['fr'],
                    ]);

                    $count++;
                });
            }
        });

        return $count;
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function syncLocale(JournalArticle $article, string $locale, array $columns): void
    {
        foreach ($columns as $column => $value) {
            $source = (string) ($article->getAttribute($column) ?? '');

            Translation::query()->updateOrCreate(
                [
                    'translatable_type' => $article->getMorphClass(),
                    'translatable_id' => $article->getKey(),
                    'locale' => $locale,
                    'column' => $column,
                ],
                [
                    'value' => $value,
                    'source_hash' => hash('sha256', $source),
                ],
            );
        }
    }
}
