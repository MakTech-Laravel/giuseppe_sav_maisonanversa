<?php

namespace App\Http\Controllers\Maison;

use App\Enums\InquiryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maison\StoreContactInquiryRequest;
use App\Http\Requests\Maison\StoreCornerInquiryRequest;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InquiryController extends Controller
{
    public function storeContact(StoreContactInquiryRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();

        $inquiry = Inquiry::query()->create([
            'type' => InquiryType::Contact,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'] ?? '',
            'meta' => $this->nullableMeta(Arr::only($validated, ['datum', 'moment', 'soort', 'ervaring'])),
            'locale' => $locale,
            'ip' => $request->ip(),
        ]);

        $this->queueBureauMail($inquiry);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Uw bericht is ontvangen.'),
        ]);

        return back();
    }

    public function storeCorner(StoreCornerInquiryRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();

        $inquiry = Inquiry::query()->create([
            'type' => InquiryType::Corner,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => 'Club Corner partnership',
            'message' => $validated['message'],
            'meta' => [
                'club_name' => $validated['club_name'],
                'location' => $validated['location'],
                'courts' => $validated['courts'],
                'format' => $validated['format'],
            ],
            'locale' => $locale,
            'ip' => $request->ip(),
        ]);

        $this->queueBureauMail($inquiry);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Aanvraag ontvangen.'),
        ]);

        return back();
    }

    private function queueBureauMail(Inquiry $inquiry): void
    {
        Mail::to(config('mail.bureau_address', config('mail.from.address')))
            ->queue(new InquiryReceived($inquiry));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>|null
     */
    private function nullableMeta(array $meta): ?array
    {
        $filtered = array_filter(
            $meta,
            static fn (mixed $value): bool => filled($value),
        );

        return $filtered === [] ? null : $filtered;
    }
}
