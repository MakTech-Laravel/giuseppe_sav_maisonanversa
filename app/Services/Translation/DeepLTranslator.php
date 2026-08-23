<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeepLTranslator
{
    public const FREE_HOST = 'https://api-free.deepl.com';

    public const PAID_HOST = 'https://api.deepl.com';

    public function configured(): bool
    {
        return filled(config('services.deepl.key'));
    }

    public function host(): string
    {
        $configured = config('services.deepl.host');

        if (filled($configured)) {
            return rtrim((string) $configured, '/');
        }

        $key = (string) config('services.deepl.key');

        return str_ends_with($key, ':fx') ? self::FREE_HOST : self::PAID_HOST;
    }

    public function targetLang(string $locale): string
    {
        $targets = config('services.deepl.targets', []);

        return is_array($targets) && isset($targets[$locale])
            ? (string) $targets[$locale]
            : strtoupper($locale);
    }

    /**
     * Translate many source strings into one target locale, preserving order.
     *
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translateMany(array $texts, string $target, ?string $source = 'NL'): array
    {
        if ($texts === []) {
            return [];
        }

        if (! $this->configured()) {
            once(function (): void {
                Log::warning('DeepL API key is missing; visitors will see Dutch source copy.');
            });

            return $texts;
        }

        $payload = [
            'text' => array_values($texts),
            'target_lang' => $this->normalizeTarget($target),
            'preserve_formatting' => true,
        ];

        if ($source !== null) {
            $payload['source_lang'] = strtoupper($source);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key'),
            ])
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->retry(2, 200, function (Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && $exception->response->status() === 429;
                })
                ->post($this->host().'/v2/translate', $payload);
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 456) {
                Log::warning('DeepL quota exceeded.');
            }

            throw $exception;
        }

        if ($response->failed()) {
            throw new RequestException($response);
        }

        /** @var list<array{text?: string}> $translations */
        $translations = $response->json('translations') ?? [];

        return array_map(
            fn (int $index): string => $translations[$index]['text'] ?? $texts[$index],
            array_keys($texts),
        );
    }

    public function translate(string $text, string $target, ?string $source = 'NL'): string
    {
        return $this->translateMany([$text], $target, $source)[0] ?? $text;
    }

    private function normalizeTarget(string $target): string
    {
        if (strlen($target) <= 2) {
            return $this->targetLang(strtolower($target));
        }

        return strtoupper($target);
    }
}
