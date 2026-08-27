<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\ReportCommunityPostRequest;
use App\Jobs\TranslateModelJob;
use App\Mail\RsvpConfirmation;
use App\Models\CommunityComment;
use App\Models\CommunityEvent;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Models\CommunityPostHide;
use App\Models\CommunityReport;
use App\Models\EventRsvp;
use App\Models\User;
use App\Notifications\ReportFiledNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $comment = CommunityComment::createQuietly([
            'community_post_id' => $communityPost->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        TranslateModelJob::dispatchSync(CommunityComment::class, $comment->id);

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
        CommunityPostHide::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'community_post_id' => $communityPost->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bericht verborgen op uw muur.')]);

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
