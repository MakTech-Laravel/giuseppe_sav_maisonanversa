<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInquirySeenRequest;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    public function appointmentsIndex(Request $request, string $locale): Response
    {
        return $this->index($request, 'appointments', InquiryType::appointmentInbox());
    }

    public function feedbackIndex(Request $request, string $locale): Response
    {
        return $this->index($request, 'feedback', [InquiryType::Feedback]);
    }

    public function appointmentsShow(string $locale, Inquiry $inquiry): Response
    {
        $this->assertInbox($inquiry, 'appointments');

        return $this->show($inquiry, 'appointments');
    }

    public function feedbackShow(string $locale, Inquiry $inquiry): Response
    {
        $this->assertInbox($inquiry, 'feedback');

        return $this->show($inquiry, 'feedback');
    }

    public function updateSeen(
        UpdateInquirySeenRequest $request,
        string $locale,
        Inquiry $inquiry,
    ): RedirectResponse {
        $this->assertInbox($inquiry, $this->inboxFromRoute($request));

        $inquiry->update([
            'seen_at' => $request->validated('seen') ? now() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status bijgewerkt.')]);

        return back();
    }

    public function destroy(Request $request, string $locale, Inquiry $inquiry): RedirectResponse
    {
        $inbox = $this->inboxFromRoute($request);

        $this->assertInbox($inquiry, $inbox);

        $inquiry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aanvraag verwijderd.')]);

        return redirect()->route("admin.{$inbox}.index", ['locale' => $locale]);
    }

    /**
     * @param  list<InquiryType>  $types
     */
    private function index(Request $request, string $inbox, array $types): Response
    {
        $filters = $this->filters($request, $inbox);

        $inquiries = Inquiry::query()
            ->whereIn('type', $types)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] === 'seen', function (Builder $query): void {
                $query->seen();
            })
            ->when($filters['status'] === 'unseen', function (Builder $query): void {
                $query->unseen();
            })
            ->when($inbox === 'appointments' && $filters['kind'] !== '', function (Builder $query) use ($filters): void {
                $query->where('type', $filters['kind']);
            })
            ->orderByDesc('priority')
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Inquiry $inquiry): array => $this->row($inquiry));

        return Inertia::render("admin/{$inbox}/index", [
            'inquiries' => $inquiries,
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'kinds' => $inbox === 'appointments'
                ? collect(InquiryType::appointmentInbox())
                    ->map(fn (InquiryType $type): array => [
                        'value' => $type->value,
                        'label' => $type->label(),
                    ])
                    ->values()
                    ->all()
                : [],
        ]);
    }

    private function show(Inquiry $inquiry, string $inbox): Response
    {
        $inquiry->markSeen();
        $inquiry->refresh();

        return Inertia::render("admin/{$inbox}/show", [
            'inquiry' => $this->details($inquiry),
        ]);
    }

    /**
     * @return array{search: string, status: string, kind: string, per_page: int}
     */
    private function filters(Request $request, string $inbox): array
    {
        $status = trim((string) $request->query('status', ''));
        $kind = trim((string) $request->query('kind', ''));

        if (! in_array($status, ['seen', 'unseen'], true)) {
            $status = '';
        }

        $allowedKinds = array_map(
            fn (InquiryType $type): string => $type->value,
            InquiryType::appointmentInbox(),
        );

        if ($inbox !== 'appointments' || ! in_array($kind, $allowedKinds, true)) {
            $kind = '';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => $status,
            'kind' => $kind,
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
     * @return array{
     *     id: string,
     *     type: string,
     *     type_label: string,
     *     name: string,
     *     email: string,
     *     subject: string|null,
     *     message: string,
     *     seen: bool,
     *     locale: string,
     *     created_at: string|null,
     *     user_id: int|null,
     *     priority: bool,
     *     sla_due_at: string|null,
     *     sla_breached: bool
     * }
     */
    private function row(Inquiry $inquiry): array
    {
        return [
            'id' => (string) $inquiry->id,
            'type' => $inquiry->type->value,
            'type_label' => $inquiry->type->label(),
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'subject' => $inquiry->subject,
            'message' => Str::limit($inquiry->message, 120),
            'seen' => $inquiry->isSeen(),
            'locale' => $inquiry->locale,
            'created_at' => $inquiry->created_at?->toIso8601String(),
            'user_id' => $inquiry->user_id,
            ...$inquiry->slaShare(),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     type_label: string,
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     subject: string|null,
     *     message: string,
     *     meta: array<string, mixed>|null,
     *     seen: bool,
     *     locale: string,
     *     ip: string|null,
     *     created_at: string|null,
     *     user_id: int|null,
     *     priority: bool,
     *     sla_due_at: string|null,
     *     sla_breached: bool
     * }
     */
    private function details(Inquiry $inquiry): array
    {
        return [
            'id' => (string) $inquiry->id,
            'type' => $inquiry->type->value,
            'type_label' => $inquiry->type->label(),
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'subject' => $inquiry->subject,
            'message' => $inquiry->message,
            'meta' => $inquiry->meta,
            'seen' => $inquiry->isSeen(),
            'locale' => $inquiry->locale,
            'ip' => $inquiry->ip,
            'created_at' => $inquiry->created_at?->toIso8601String(),
            'user_id' => $inquiry->user_id,
            ...$inquiry->slaShare(),
        ];
    }

    private function inboxFromRoute(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, 'feedback') ? 'feedback' : 'appointments';
    }

    private function assertInbox(Inquiry $inquiry, string $inbox): void
    {
        $allowed = $inbox === 'feedback'
            ? [InquiryType::Feedback]
            : InquiryType::appointmentInbox();

        abort_unless(in_array($inquiry->type, $allowed, true), 404);
    }
}
