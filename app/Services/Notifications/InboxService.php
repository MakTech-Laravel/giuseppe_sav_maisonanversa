<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read side of the in-app inbox. Every query is scoped to the owner, so another
 * user's row id resolves to a 404.
 */
class InboxService
{
    public const STATUSES = ['all', 'unread', 'read'];

    public function unreadCount(User $user): int
    {
        return $this->query($user)->unread()->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preview(User $user, int $limit = 5): array
    {
        return $this->query($user)
            ->with('notification')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (UserNotification $row): array => $this->toItem($row))
            ->values()
            ->all();
    }

    /**
     * @param  array{status?: string|null, type?: string|null, search?: string|null}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateFor(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $status = in_array($filters['status'] ?? null, self::STATUSES, true) ? $filters['status'] : 'all';
        $type = NotificationType::tryFrom((string) ($filters['type'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->query($user)
            ->with('notification')
            ->when($status === 'unread', fn (Builder $query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn (Builder $query) => $query->whereNotNull('read_at'))
            ->when($type !== null, fn (Builder $query) => $query->whereHas(
                'notification',
                fn (Builder $notification) => $notification->where('type', $type->value),
            ))
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'notification',
                fn (Builder $notification) => $notification->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                }),
            ))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (UserNotification $row): array => $this->toItem($row));
    }

    public function findFor(User $user, int|string $id): UserNotification
    {
        return $this->query($user)
            ->with('notification')
            ->findOrFail($id);
    }

    public function markRead(User $user, UserNotification $row): void
    {
        $this->ensureOwner($user, $row);

        if ($row->read_at === null) {
            $row->update(['read_at' => now()]);
        }
    }

    public function markAllRead(User $user): int
    {
        return $this->query($user)->unread()->update(['read_at' => now()]);
    }

    public function delete(User $user, UserNotification $row): void
    {
        $this->ensureOwner($user, $row);

        $row->delete();
    }

    /**
     * @return array{id: int, type: string|null, type_label: string|null, title: string, message: string, href: string|null, link_label: string|null, read: bool, read_at: string|null, created_at: string|null, created_at_human: string}
     */
    public function toItem(UserNotification $row): array
    {
        $notification = $row->notification;
        $data = is_array($notification?->data) ? $notification->data : [];

        return [
            'id' => $row->id,
            'type' => $notification?->type->value,
            'type_label' => $notification ? __($notification->type->label()) : null,
            'title' => $notification?->title ? __($notification->title) : '',
            'message' => $notification?->message ? __($notification->message) : '',
            'href' => is_string($data['link_url'] ?? null) ? $data['link_url'] : null,
            'link_label' => is_string($data['link_label'] ?? null) ? __($data['link_label']) : null,
            'read' => $row->read_at !== null,
            'read_at' => $row->read_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
            'created_at_human' => $row->created_at?->diffForHumans() ?? '',
        ];
    }

    /**
     * @return Builder<UserNotification>
     */
    private function query(User $user): Builder
    {
        return UserNotification::query()->where('user_id', $user->id);
    }

    private function ensureOwner(User $user, UserNotification $row): void
    {
        if ($row->user_id !== $user->id) {
            throw (new ModelNotFoundException)->setModel(UserNotification::class, [$row->id]);
        }
    }
}
