<?php

namespace App\Support;

use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class CommunityFeed
{
    public const PER_PAGE = 8;

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public static function paginate(Request $request, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $locale = $request->route('locale');
        $pathLocale = is_string($locale) ? $locale : app()->getLocale();
        $userId = $request->user()?->id;

        $query = CommunityPost::query()
            ->with(['author', 'comments.author', 'translations', 'comments.translations'])
            ->withCount('likes')
            ->where(function ($query) use ($userId): void {
                $query->where(function ($visible): void {
                    $visible->where('status', 'published')
                        ->whereNull('hidden_at');
                });

                if ($userId !== null) {
                    $query->orWhere('author_id', $userId);
                }
            })
            ->latest();

        if ($userId !== null) {
            $query->whereDoesntHave('hides', fn ($hides) => $hides->where('user_id', $userId));

            $query->withExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $userId),
            ]);
        }

        $paginator = $query
            ->paginate($perPage)
            ->withPath(route('maison.community', ['locale' => $pathLocale]));

        $paginator->setCollection(
            $paginator->getCollection()->map(function (CommunityPost $post) use ($pathLocale): array {
                $initials = strtoupper(substr($post->author->name, 0, 2));
                $content = $post->translated('content', $pathLocale);

                return [
                    'id' => (string) $post->id,
                    'userAuthored' => true,
                    'official' => $post->is_official,
                    'initials' => $initials,
                    'avatarBg' => $post->is_official ? '#1a1208' : '#3d2a18',
                    'name' => $post->author->name,
                    'info' => $post->created_at?->diffForHumans() ?? '',
                    'badge' => $post->is_official ? __('Officieel') : __('Lid'),
                    'badgeOfficial' => $post->is_official,
                    'content' => $content,
                    'excerpt' => CommunityPostPresenter::excerpt($content),
                    'is_truncated' => CommunityPostPresenter::isTruncated($content),
                    'likes' => $post->likes_count,
                    'liked' => (bool) ($post->liked ?? false),
                    'comments' => $post->comments->map(fn ($comment) => [
                        'id' => (string) $comment->id,
                        'name' => $comment->author->name,
                        'initials' => strtoupper(substr($comment->author->name, 0, 2)),
                        'body' => $comment->translated('body', $pathLocale),
                        'info' => $comment->created_at?->diffForHumans() ?? '',
                    ])->all(),
                ];
            })
        );

        return $paginator;
    }
}
