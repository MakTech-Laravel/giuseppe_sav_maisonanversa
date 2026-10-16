<?php

namespace App\Http\Requests\Community;

use App\Models\CommunitySession;
use App\Services\Community\SessionChatService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreSessionChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendMessage', $this->communitySession()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:'.SessionChatService::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $body = trim((string) $this->input('body', ''));
                $images = $this->file('images', []);

                if ($body === '' && $images === []) {
                    $validator->errors()->add(
                        'body',
                        __('Schrijf een bericht of voeg een afbeelding toe.'),
                    );
                }
            },
        ];
    }

    public function messageBody(): ?string
    {
        $body = trim((string) $this->input('body', ''));

        return $body === '' ? null : $body;
    }

    /**
     * @return list<UploadedFile>
     */
    public function images(): array
    {
        $files = $this->file('images', []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter(
            is_array($files) ? $files : [],
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }

    private function communitySession(): CommunitySession
    {
        /** @var CommunitySession $session */
        $session = $this->route('communitySession');

        return $session;
    }
}
