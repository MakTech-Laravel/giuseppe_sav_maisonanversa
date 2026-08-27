<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Exports\NewsletterSubscribersExport;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HeritageLetterController extends Controller
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    public function index(Request $request, string $locale): Response
    {
        $filters = $this->filters($request);

        $subscribers = $this->filteredQuery($filters)
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (NewsletterSubscriber $subscriber): array => $this->row($subscriber));

        return Inertia::render('admin/letter/index', [
            'subscribers' => $subscribers,
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'letterConnected' => filled(config('services.brevo.api_key')),
        ]);
    }

    public function export(Request $request, string $locale): BinaryFileResponse
    {
        $subscribers = $this->filteredQuery($this->filters($request))
            ->orderBy('id')
            ->get();

        return Excel::download(new NewsletterSubscribersExport($subscribers), 'heritage-letter.csv');
    }

    /**
     * @param  array{search: string, status: string, source: string, locale: string, per_page: int}  $filters
     * @return Builder<NewsletterSubscriber>
     */
    private function filteredQuery(array $filters): Builder
    {
        return NewsletterSubscriber::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when($filters['source'] !== '', function (Builder $query) use ($filters): void {
                $query->where('source', $filters['source']);
            })
            ->when($filters['locale'] !== '', function (Builder $query) use ($filters): void {
                $query->where('locale', $filters['locale']);
            });
    }

    /**
     * @return array{search: string, status: string, source: string, locale: string, per_page: int}
     */
    private function filters(Request $request): array
    {
        $status = trim((string) $request->query('status', ''));
        $source = trim((string) $request->query('source', ''));
        $subscriberLocale = trim((string) $request->query('subscriber_locale', ''));

        $statusValues = array_map(
            fn (SubscriberStatus $case): string => $case->value,
            SubscriberStatus::cases(),
        );
        $sourceValues = array_map(
            fn (SubscriberSource $case): string => $case->value,
            SubscriberSource::cases(),
        );
        $locales = config('maison.locales');

        if (! in_array($status, $statusValues, true)) {
            $status = '';
        }

        if (! in_array($source, $sourceValues, true)) {
            $source = '';
        }

        if (! in_array($subscriberLocale, $locales, true)) {
            $subscriberLocale = '';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => $status,
            'source' => $source,
            'locale' => $subscriberLocale,
            'per_page' => $this->perPage($request),
        ];
    }

    private function perPage(Request $request): int
    {
        $value = $request->query('per_page');

        if (is_numeric($value)) {
            $perPage = (int) $value;

            if ($perPage > 0 && in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
                return $perPage;
            }
        }

        return self::PER_PAGE_DEFAULT;
    }

    /**
     * @return array{id: int, email: string, name: string, status: string, source: string, locale: string, joined_at: string|null, synced_at: string|null, preferences: array{heritageLetter: bool, productUpdates: bool, events: bool}}
     */
    private function row(NewsletterSubscriber $subscriber): array
    {
        return [
            'id' => $subscriber->id,
            'email' => $subscriber->email,
            'name' => $subscriber->name ?: $subscriber->email,
            'status' => $subscriber->status->value,
            'source' => $subscriber->source->value,
            'locale' => $subscriber->locale,
            'joined_at' => $subscriber->consent_at?->toDateString() ?? $subscriber->created_at?->toDateString(),
            'synced_at' => $subscriber->synced_at?->toDateTimeString(),
            'preferences' => $subscriber->topicPreferences(),
        ];
    }
}
