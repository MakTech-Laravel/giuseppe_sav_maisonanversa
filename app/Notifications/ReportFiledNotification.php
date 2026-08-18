<?php

namespace App\Notifications;

use App\Models\CommunityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportFiledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CommunityReport $report,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Community-melding'),
            'body' => __('Een community-post is gemeld: :reason', [
                'reason' => $this->report->reason,
            ]),
            'report_id' => $this->report->id,
            'community_post_id' => $this->report->community_post_id,
            'reason' => $this->report->reason,
        ];
    }
}
