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
            return $this->normalizeHost((string) $configured);
        }

        $key = (string) config('services.deepl.key');

        return str_ends_with($key, ':fx') ? self::FREE_HOST : self::PAID_HOST;
    }

    private function normalizeHost(string $host): string
    {
        $host = rtrim($host, '/');

        if (! str_starts_with($host, 'http://') && ! str_starts_with($host, 'https://')) {
            $host = 'https://'.$host;
        }

        return $host;
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
    public function translateMany(array $texts, string $target, ?string $source = 'NL', bool $html = false): array
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

        $spellings = [];
        $payloadTexts = [];

        foreach (array_values($texts) as $index => $text) {
            $spellings[$index] = [];
            $payloadTexts[$index] = $this->maskBrandName($text, $spellings[$index]);
        }

        $protectsBrand = collect($spellings)->contains(
            fn (array $matches): bool => $matches !== [],
        );

        $payload = [
            'text' => $payloadTexts,
            'target_lang' => $this->normalizeTarget($target),
            'preserve_formatting' => true,
        ];

        if ($html) {
            $payload['tag_handling'] = 'html';
        } elseif ($protectsBrand) {
            $payload['tag_handling'] = 'xml';
        }

        if ($protectsBrand) {
            $payload['ignore_tags'] = 'x';
        }

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
            fn (int $index): string => $this->restoreBrandName(
                $translations[$index]['text'] ?? $payloadTexts[$index],
                $spellings[$index] ?? [],
            ),
            array_keys($payloadTexts),
        );
    }

    public function translate(string $text, string $target, ?string $source = 'NL', bool $html = false): string
    {
        return $this->translateMany([$text], $target, $source, $html)[0] ?? $text;
    }

    private function normalizeTarget(string $target): string
    {
        if (strlen($target) <= 2) {
            return $this->targetLang(strtolower($target));
        }

        return strtoupper($target);
    }

    /**
     * Wraps every fixed name (config maison.fixed_names) in an ignored tag so DeepL
     * leaves it as written. "Maison Anversa" matches in any case; the others match
     * as written, so a Dutch common word such as "atelier" in prose is translated.
     *
     * @param  list<string>  $spellings
     */
    private function maskBrandName(string $text, array &$spellings): string
    {
        $masked = preg_replace_callback(
            $this->fixedNamesPattern(),
            function (array $match) use (&$spellings): string {
                $index = count($spellings);
                $spellings[] = $match[0];

                return '<x id="'.$index.'">'.$index.'</x>';
            },
            $text,
        );

        return is_string($masked) ? $masked : $text;
    }

    private function fixedNamesPattern(): string
    {
        /** @var list<string> $names */
        $names = array_values(array_filter((array) config('maison.fixed_names', ['Maison Anversa'])));

        usort($names, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $parts = array_map(
            fn (string $name): string => $name === 'Maison Anversa'
                ? '(?i:Maison\s+Anversa)'
                : preg_quote($name, '/'),
            $names,
        );

        return '/(?<![\p{L}\p{N}])(?:'.implode('|', $parts).')(?![\p{L}\p{N}])/u';
    }

    /**
     * @param  list<string>  $spellings
     */
    private function restoreBrandName(string $text, array $spellings): string
    {
        if ($spellings === []) {
            return $text;
        }

        $restored = preg_replace_callback(
            '/<x id="(\d+)">.*?<\/x>/s',
            function (array $match) use ($spellings): string {
                $index = (int) $match[1];

                return $spellings[$index] ?? $match[0];
            },
            $text,
        );

        return is_string($restored) ? $restored : $text;
    }
}
