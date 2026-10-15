<?php

namespace App\Services\Community;

use App\Models\CommunitySession;
use App\Support\MatchCardPresenter;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Turns a session into a Story or Post PNG through the Satori + resvg script
 * in resources/match-card. Each image is cached per format, locale and
 * content hash, so a new official score, roster change or privacy toggle
 * renders a fresh card and the stale one is dropped.
 */
class MatchCardRenderer
{
    public function __construct(private MatchCardPresenter $presenter) {}

    /**
     * @return array{hash: string, png: string}
     */
    public function render(CommunitySession $session, string $locale, string $format): array
    {
        $payload = $this->presenter->payload($session, $locale, $format);
        $hash = $this->presenter->hash($payload);
        $directory = 'match-cards/'.$session->getKey();
        $prefix = $format.'-'.$payload['locale'].'-';
        $path = $directory.'/'.$prefix.$hash.'.png';
        $disk = $this->disk();

        if ($disk->exists($path)) {
            return ['hash' => $hash, 'png' => (string) $disk->get($path)];
        }

        $png = $this->run($payload);

        foreach ($disk->files($directory) as $stale) {
            if (str_starts_with(basename($stale), $prefix)) {
                $disk->delete($stale);
            }
        }

        $disk->put($path, $png);

        return ['hash' => $hash, 'png' => $png];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function run(array $payload): string
    {
        $output = tempnam(sys_get_temp_dir(), 'match-card-');

        try {
            $result = Process::timeout(60)
                ->input((string) json_encode($payload, JSON_UNESCAPED_UNICODE))
                ->run([
                    (string) config('maison.match_cards.node_binary'),
                    (string) config('maison.match_cards.script'),
                    $output,
                ]);

            $png = is_file($output) ? (string) file_get_contents($output) : '';

            if ($result->failed() || $png === '') {
                throw new RuntimeException('Match card render failed: '.trim($result->errorOutput()));
            }

            return $png;
        } finally {
            if (is_file($output)) {
                @unlink($output);
            }
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('maison.match_cards.disk'));
    }
}
