<?php

namespace App\Models;

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'name',
        'locale',
        'source',
        'status',
        'consent_at',
        'consent_ip',
        'consent_user_agent',
        'brevo_contact_id',
        'synced_at',
        'unsubscribe_token',
        'preferences',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => SubscriberSource::class,
            'status' => SubscriberStatus::class,
            'consent_at' => 'datetime',
            'synced_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NewsletterSubscriber $subscriber): void {
            if (blank($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = (string) Str::uuid();
            }
        });
    }

    public function isSubscribed(): bool
    {
        return $this->status === SubscriberStatus::Subscribed;
    }
}
