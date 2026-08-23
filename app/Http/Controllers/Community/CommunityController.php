<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\ReportCommunityPostRequest;
use App\Mail\RsvpConfirmation;
use App\Models\CommunityComment;
use App\Models\CommunityEvent;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\EventRsvp;
use App\Models\User;
use App\Notifications\ReportFiledNotification;
use App\Notifications\SessionJoinedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class CommunityController extends Controller
{
    public function storePost(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'is_official' => ['sometimes', 'boolean'],
        ]);

        $official = (bool) ($data['is_official'] ?? false);

        abort_if($official && ! $request->user()->can('community.official'), 403);

        CommunityPost::query()->create([
            'author_id' => $request->user()->id,
            'content' => $data['content'],
            'is_official' => $official,
            'status' => 'published',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post geplaatst in de Community.')]);

        return back();
    }

    public function storeComment(Request $request, string $locale, CommunityPost $communityPost): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        CommunityComment::query()->create([
            'community_post_id' => $communityPost->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }

    public function toggleLike(Request $request, string $locale, CommunityPost $communityPost): RedirectResponse
    {
        $like = CommunityLike::query()->where([
            'community_post_id' => $communityPost->id,
            'user_id' => $request->user()->id,
        ])->first();

        if ($like) {
            $like->delete();
        } else {
            CommunityLike::query()->create([
                'community_post_id' => $communityPost->id,
                'user_id' => $request->user()->id,
            ]);
        }

        return back();
    }

    public function hidePost(Request $request, string $locale, CommunityPost $communityPost): RedirectResponse
    {
        abort_unless($request->user()->can('community.moderate'), 403);

        $communityPost->update([
            'status' => 'hidden',
            'hidden_at' => now(),
        ]);

        return back();
    }

    public function reportPost(ReportCommunityPostRequest $request, string $locale, CommunityPost $communityPost): RedirectResponse
    {
        $report = CommunityReport::query()->create([
            'reporter_id' => $request->user()->id,
            'community_post_id' => $communityPost->id,
            'reason' => $request->validated('reason'),
            'status' => 'open',
        ]);

        $moderators = User::permission('community.moderate')->get();

        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new ReportFiledNotification($report));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bericht gemeld.')]);

        return back();
    }

    public function storeSession(Request $request, string $locale): RedirectResponse
    {
        abort_unless($request->user()->isFoundingCircle(), 403);

        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'level' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = CommunitySession::query()->create([
            ...$data,
            'host_id' => $request->user()->id,
        ]);

        CommunitySessionParticipant::query()->create([
            'community_session_id' => $session->id,
            'user_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie aangemaakt.')]);

        return back();
    }

    public function joinSession(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        abort_unless($request->user()->isFoundingCircle(), 403);

        DB::transaction(function () use ($request, $communitySession): void {
            $locked = CommunitySession::query()->whereKey($communitySession->id)->lockForUpdate()->firstOrFail();

            $already = CommunitySessionParticipant::query()->where([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
            ])->exists();

            if ($already) {
                return;
            }

            if ($locked->capacity !== null) {
                $count = CommunitySessionParticipant::query()
                    ->where('community_session_id', $locked->id)
                    ->count();

                abort_if($count >= $locked->capacity, 422, __('Deze sessie is vol.'));
            }

            CommunitySessionParticipant::query()->create([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
            ]);

            $locked->host->notify(new SessionJoinedNotification($locked, $request->user()->name));
        });

        return back();
    }

    public function leaveSession(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back();
    }

    public function rsvpEvent(Request $request, string $locale, CommunityEvent $communityEvent): RedirectResponse
    {
        abort_unless($request->user() !== null, 401);

        $existing = EventRsvp::query()->where([
            'community_event_id' => $communityEvent->id,
            'user_id' => $request->user()->id,
        ])->first();

        if ($existing !== null) {
            return back();
        }

        $communityEvent->loadCount('rsvps');

        abort_if($communityEvent->isFull(), 422, __('Dit evenement is vol.'));

        EventRsvp::query()->create([
            'community_event_id' => $communityEvent->id,
            'user_id' => $request->user()->id,
        ]);

        Mail::to($request->user())->queue(new RsvpConfirmation($communityEvent));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('U bent aangemeld voor dit evenement.')]);

        return back();
    }

    public function cancelRsvp(Request $request, string $locale, CommunityEvent $communityEvent): RedirectResponse
    {
        abort_unless($request->user() !== null, 401);

        EventRsvp::query()
            ->where('community_event_id', $communityEvent->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Uw aanmelding is geannuleerd.')]);

        return back();
    }
}
