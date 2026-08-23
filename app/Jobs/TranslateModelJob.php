<?php

namespace App\Jobs;

use App\Models\Concerns\TranslatesWithDeepL;
use App\Services\Translation\DeepLTranslator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;

class TranslateModelJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        public string $modelClass,
        public int $modelId,
    ) {}

    public function uniqueId(): string
    {
        return $this->modelClass.':'.$this->modelId;
    }

    public function handle(DeepLTranslator $translator): void
    {
        $model = $this->modelClass::query()->find($this->modelId);

        if ($model === null || ! in_array(TranslatesWithDeepL::class, class_uses_recursive($model), true)) {
            return;
        }

        /** @var Model&object{translatableColumns: callable, translations: mixed} $model */
        $columns = $model->translatableColumns();
        $targets = collect($model->translationTargetLocales());

        if ($columns === [] || $targets->isEmpty()) {
            return;
        }

        $sourceLocale = $model->translationUsesAutoDetect()
            ? null
            : strtoupper((string) config('maison.default_locale'));

        $model->loadMissing('translations');

        foreach ($targets as $locale) {
            $pending = [];

            foreach ($columns as $column) {
                $source = trim((string) ($model->getAttribute($column) ?? ''));

                if ($source === '') {
                    continue;
                }

                $hash = $model->translationSourceHash($column);
                $existing = $model->translations->first(
                    fn ($translation): bool => $translation->locale === $locale
                        && $translation->column === $column
                        && $translation->source_hash === $hash,
                );

                if ($existing !== null) {
                    continue;
                }

                $pending[$column] = $source;
            }

            if ($pending === []) {
                continue;
            }

            if (! $translator->configured()) {
                return;
            }

            try {
                $translated = $translator->translateMany(
                    array_values($pending),
                    $translator->targetLang($locale),
                    $sourceLocale,
                );
            } catch (RequestException $exception) {
                if ($exception->response?->status() === 456) {
                    $this->release(3600);

                    return;
                }

                throw $exception;
            }

            $index = 0;

            foreach (array_keys($pending) as $column) {
                $model->translations()->updateOrCreate(
                    [
                        'locale' => $locale,
                        'column' => $column,
                    ],
                    [
                        'value' => $translated[$index] ?? $pending[$column],
                        'source_hash' => $model->translationSourceHash($column),
                    ],
                );
                $index++;
            }

            $model->unsetRelation('translations');
            $model->load('translations');
        }
    }
}
