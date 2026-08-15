<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Translate many source strings into one target locale, preserving order.
     *
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translateMany(array $texts, string $target, string $source = 'NL'): array
    {
        if ($texts === []) {
            return [];
        }

        if (! $this->configured()) {
            return $texts;
        }

        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key'),
        ])
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 200)
            ->post($this->host().'/v2/translate', [
                'text' => array_values($texts),
                'source_lang' => strtoupper($source),
                'target_lang' => strtoupper($target),
                'preserve_formatting' => true,
            ]);

        if ($response->status() === 456) {
            Log::warning('DeepL quota exceeded.');
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

    public function translate(string $text, string $target, string $source = 'NL'): string
    {
        return $this->translateMany([$text], $target, $source)[0] ?? $text;
    }
}
