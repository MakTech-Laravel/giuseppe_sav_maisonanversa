<?php

namespace App\Models\Concerns;

use App\Jobs\TranslateModelJob;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @mixin Model
 *
 * @property-read Collection<int, Translation> $translations
 */
trait TranslatesWithDeepL
{
    /**
     * @var list<string>
     */
    private const TRANSLATION_DENY_EXACT = [
        'id',
        'email',
        'password',
        'token',
        'slug',
        'username',
        'name',
        'uuid',
        'locale',
        'status',
        'monogram',
        'gift_message',
        'phone',
        'ip',
        'level',
        'currency',
        'type',
        'sold_out_behavior',
        'remember_token',
        'url',
        'website',
        'href',
    ];

    /**
     * @var list<string>
     */
    private const TRANSLATION_STRING_TYPES = [
        'char',
        'varchar',
        'string',
        'text',
        'tinytext',
        'mediumtext',
        'longtext',
        'clob',
        'nvarchar',
        'ntext',
    ];

    public static function bootTranslatesWithDeepL(): void
    {
        static::created(function (Model $model): void {
            $model->dispatchDeepLTranslation();
        });

        static::updated(function (Model $model): void {
            if ($model->translationSourceIsDirty()) {
                $model->dispatchDeepLTranslation();
            }
        });

        static::deleted(function (Model $model): void {
            $model->translations()->delete();
        });
    }

    public function initializeTranslatesWithDeepL(): void
    {
        $this->with = array_values(array_unique([
            ...$this->with,
            'translations',
        ]));
    }

    /**
     * @return MorphMany<Translation, $this>
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * @return list<string>
     */
    public function translatableColumns(): array
    {
        $except = $this->translationExcept ?? [];

        if (property_exists($this, 'translatable') && is_array($this->translatable)) {
            return array_values(array_diff($this->translatable, $except));
        }

        return collect(Schema::getColumns($this->getTable()))
            ->filter(function (array $column) use ($except): bool {
                $name = $column['name'];
                $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));

                if (! in_array($type, self::TRANSLATION_STRING_TYPES, true)) {
                    return false;
                }

                if (in_array($name, $except, true) || $this->isDeniedTranslationColumn($name)) {
                    return false;
                }

                return true;
            })
            ->pluck('name')
            ->values()
            ->all();
    }

    public function translated(string $column, ?string $locale = null): string
    {
        $source = (string) ($this->getAttribute($column) ?? '');
        $locale ??= app()->getLocale();
        $sourceLocale = (string) config('maison.default_locale');

        if ($source === '' || $locale === $sourceLocale) {
            return $source;
        }

        $this->loadMissing('translations');

        $row = $this->translations->first(
            fn (Translation $translation): bool => $translation->locale === $locale
                && $translation->column === $column,
        );

        return filled($row?->value) ? (string) $row->value : $source;
    }

    /**
     * @return array<string, string>
     */
    public function translationPayload(?string $locale = null): array
    {
        return collect($this->translatableColumns())
            ->mapWithKeys(fn (string $column): array => [
                $column => $this->translated($column, $locale),
            ])
            ->all();
    }

    public function translationSourceHash(string $column): string
    {
        return hash('sha256', (string) ($this->getAttribute($column) ?? ''));
    }

    /**
     * @return list<string>
     */
    public function translationTargetLocales(): array
    {
        return collect(config('maison.locales'))
            ->reject(fn (string $locale): bool => $locale === config('maison.default_locale'))
            ->values()
            ->all();
    }

    public function translationUsesAutoDetect(): bool
    {
        return false;
    }

    /**
     * @param  list<string>|null  $onlyLocales
     */
    public function dispatchDeepLTranslation(?array $onlyLocales = null): void
    {
        if ($this->translatableColumns() === [] || ! $this->exists) {
            return;
        }

        TranslateModelJob::dispatch($this::class, (int) $this->getKey(), $onlyLocales);
    }

    public function translationSourceIsDirty(): bool
    {
        return collect($this->translatableColumns())
            ->contains(fn (string $column): bool => $this->wasChanged($column));
    }

    private function isDeniedTranslationColumn(string $name): bool
    {
        if (in_array($name, self::TRANSLATION_DENY_EXACT, true)) {
            return true;
        }

        if (str_ends_with($name, '_id') || str_ends_with($name, '_at') || str_ends_with($name, '_url')) {
            return true;
        }

        if (Str::startsWith($name, 'stripe_')) {
            return true;
        }

        return Str::contains($name, ['token', 'password', 'hash', 'secret']);
    }
}
