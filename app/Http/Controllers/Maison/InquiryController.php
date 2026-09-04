<?php

namespace App\Http\Controllers\Maison;

use App\Enums\InquiryType;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maison\StoreContactInquiryRequest;
use App\Http\Requests\Maison\StoreCornerInquiryRequest;
use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\InquiryDeviceCookie;
use App\Support\MailLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InquiryController extends Controller
{
    public function __construct(private InquiryDeviceCookie $deviceCookie) {}

    public function storeContact(StoreContactInquiryRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();
        $kind = InquiryType::from($validated['kind']);
        $deviceToken = $this->deviceCookie->remember($request);

        $inquiry = Inquiry::query()->create([
            'type' => $kind,
            'user_id' => $request->user()?->id,
            'priority' => $this->isPriorityRequester($request->user()),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $kind->subject(),
            'message' => $validated['message'] ?? '',
            'meta' => $this->nullableMeta(Arr::only($validated, ['datum', 'moment', 'soort', 'ervaring'])),
            'locale' => $locale,
            'ip' => $request->ip(),
            'device_token' => $deviceToken,
        ]);

        $this->queueMails($inquiry);

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
            'user_id' => $request->user()?->id,
            'priority' => $this->isPriorityRequester($request->user()),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => InquiryType::Corner->subject(),
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

        Mail::to(config('mail.bureau_address', config('mail.from.address')))
            ->locale(MailLocale::resolve($locale))
            ->queue(new InquiryReceived($inquiry));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Aanvraag ontvangen.'),
        ]);

        return back();
    }

    private function isPriorityRequester(?User $user): bool
    {
        return $user !== null && $user->hasRole(RoleEnum::FOUNDING_CIRCLE->value);
    }

    private function queueMails(Inquiry $inquiry): void
    {
        $mailLocale = MailLocale::resolve($inquiry->locale);

        Mail::to(config('mail.bureau_address', config('mail.from.address')))
            ->locale($mailLocale)
            ->queue(new InquiryReceived($inquiry));

        Mail::to($inquiry->email)
            ->locale($mailLocale)
            ->queue(new InquiryConfirmation($inquiry));
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
