import type { SessionPlayer } from '@/types/session';

export type ChatAttachment = {
    id: string;
    url: string;
    name: string;
};

export type ChatMessageStatus = 'sending' | 'failed' | 'sent';

export type ChatMessage = {
    id: string;
    author: SessionPlayer;
    body: string | null;
    attachments: ChatAttachment[];
    sent_at: string;
    /** Client-only: optimistic send lifecycle. Server messages are treated as sent. */
    status?: ChatMessageStatus;
};
