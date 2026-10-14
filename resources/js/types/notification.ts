export type NotificationType =
    | 'info'
    | 'success'
    | 'warning'
    | 'alert'
    | 'order'
    | 'community'
    | 'system';

/** Mirrors App\Services\Notifications\InboxService::toItem(). */
export type NotificationItem = {
    id: number;
    type: NotificationType | null;
    type_label: string | null;
    title: string;
    message: string;
    href: string | null;
    link_label: string | null;
    read: boolean;
    read_at: string | null;
    created_at: string | null;
    created_at_human: string;
};

export type SharedNotifications = {
    unreadCount: number;
    preview: NotificationItem[];
};

export type NotificationStatusFilter = 'all' | 'unread' | 'read';
