<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateCommunityPostRequest;
use App\Http\Requests\Admin\UpdateCommunityPostRequest;
use App\Http\Requests\Admin\UpdateCommunityPostTranslationsRequest;
use App\Models\CommunityPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommunityPostController extends Controller
{
    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['content'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

    public function update(
        UpdateCommunityPostRequest $request,
        string $locale,
        CommunityPost $communityPost,
    ): RedirectResponse {
        $this->ensureAuthor($request, $communityPost);

        $communityPost->update([
            'content' => $request->validated('content'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bericht bijgewerkt.')]);

        return back();
    }

    public function updateTranslations(
        UpdateCommunityPostTranslationsRequest $request,
        string $locale,
        CommunityPost $communityPost,
    ): RedirectResponse {
        $this->ensureAuthor($request, $communityPost);

        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $communityPost->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $communityPost->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return back();
    }

    public function translate(
        TranslateCommunityPostRequest $request,
        string $locale,
        CommunityPost $communityPost,
    ): RedirectResponse {
        $this->ensureAuthor($request, $communityPost);

        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $communityPost->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $communityPost->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $communityPost->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $communityPost->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return back();
    }

    private function ensureAuthor(Request $request, CommunityPost $communityPost): void
    {
        abort_unless($communityPost->author_id === $request->user()?->id, 403);
    }
}
