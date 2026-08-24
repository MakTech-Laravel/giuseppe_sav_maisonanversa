<?php

namespace App\Exports;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NewsletterSubscribersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, NewsletterSubscriber>  $subscribers
     */
    public function __construct(private Collection $subscribers) {}

    /**
     * @return Collection<int, NewsletterSubscriber>
     */
    public function collection()
    {
        return $this->subscribers;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Email', 'Name', 'Locale', 'Source', 'Status', 'Consent At', 'Synced At'];
    }

    /**
     * @param  NewsletterSubscriber  $subscriber
     * @return array<int, mixed>
     */
    public function map($subscriber): array
    {
        return [
            $subscriber->email,
            $subscriber->name,
            $subscriber->locale,
            $subscriber->source->value,
            $subscriber->status->value,
            $subscriber->consent_at?->toDateTimeString(),
            $subscriber->synced_at?->toDateTimeString(),
        ];
    }
}
