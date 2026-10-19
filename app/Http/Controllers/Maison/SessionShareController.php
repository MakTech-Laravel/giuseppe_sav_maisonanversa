<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Models\CommunitySession;
use App\Services\Community\MatchCardRenderer;
use App\Support\MatchCardPresenter;
use App\Support\SessionFeed;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public face of a session. Anyone holding the share link sees who plays
 * and the result; the organiser and confirmed players also get the match card
 * to share and download.
 */
class SessionShareController extends Controller
{
    public function __construct(
        private MatchCardPresenter $cards,
        private MatchCardRenderer $renderer,
    ) {}

    public function show(Request $request, string $locale, string $shareToken): Response
    {
        $session = $this->session($shareToken);
        $viewer = $request->user();

        $session->load(['host', 'club', 'participants.user', 'participantRecords', 'translations', 'officialScore'])
            ->loadCount(['participants', 'joinRequests']);

        return Inertia::render('maison/sessions/share', [
            'session' => SessionFeed::toCard($session, $viewer, $locale),
            'players' => $this->cards->players($session, $locale),
            'result' => $session->officialScore?->setsLine(),
            'card' => Gate::allows('shareCard', $session) ? $this->cards->links($session, $locale) : null,
            'shareUrl' => $session->isCancelled() ? null : $session->shareUrl($locale),
            'memberUrl' => $viewer !== null
                ? route('community.sessions.show', ['locale' => $locale, 'communitySession' => $session->id])
                : null,
        ]);
    }

    /**
     * The image stays public so link previews (og:image) work without a
     * login; downloading is for players or a signed link from their mail.
     */
    public function card(Request $request, string $locale, string $shareToken, string $format): HttpResponse
    {
        $session = $this->session($shareToken);
        $download = $request->boolean('download');

        abort_if($session->isCancelled(), 404);
        abort_if(
            $download && ! $request->hasValidSignature() && Gate::denies('shareCard', $session),
            403,
        );

        ['hash' => $hash, 'png' => $png] = $this->renderer->render($session, $locale, $format);

        $response = response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => $download ? 'private, no-cache' : 'public, no-cache',
        ]);

        if ($download) {
            $filename = sprintf(
                'maison-anversa-%s-%s-%s.png',
                $session->sport->value,
                $session->starts_at->format('Y-m-d'),
                $format,
            );

            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }

        $response->setEtag($hash);
        $response->isNotModified($request);

        return $response;
    }

    private function session(string $shareToken): CommunitySession
    {
        return CommunitySession::query()
            ->where('share_token', $shareToken)
            ->firstOrFail();
    }
}
