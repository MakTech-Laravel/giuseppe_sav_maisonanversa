import { Calendar, IdCard, Mail, ShieldCheck } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { avatarUrl } from '@/types/admin';
import type { AdminUser } from '@/types/admin';
import { AdminPanel } from './admin-resource-shell';

interface AdminUserProfileProps {
    user: AdminUser;
    idLabel: string;
}

export function AdminUserProfile({ user, idLabel }: AdminUserProfileProps) {
    const { t } = useTranslation();
    const url = avatarUrl(user.avatar);
    const verified = Boolean(user.email_verified_at);

    return (
        <AdminPanel flush>
            <div className="relative overflow-hidden border-b bg-gradient-to-br from-primary/12 via-muted/40 to-card px-6 py-8">
                <div className="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-primary/10 blur-2xl" />
                <div className="relative flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
                    <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-primary/20 bg-card text-3xl font-semibold text-primary shadow-sm">
                        {url ? (
                            <img
                                src={url}
                                alt={user.name}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            user.name.charAt(0).toUpperCase()
                        )}
                    </div>
                    <div className="min-w-0 space-y-2">
                        <div>
                            <h2 className="truncate text-xl font-semibold tracking-tight">
                                {user.name}
                            </h2>
                            <p className="truncate text-sm text-muted-foreground">
                                {user.email}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <Badge variant={verified ? 'default' : 'secondary'}>
                                {verified
                                    ? t('Geverifieerd')
                                    : t('In afwachting')}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
            <dl className="divide-y text-sm">
                <ProfileRow
                    icon={IdCard}
                    label={idLabel}
                    value={`#${user.id}`}
                />
                <ProfileRow
                    icon={Mail}
                    label={t('E-mail')}
                    value={user.email}
                />
                <ProfileRow
                    icon={ShieldCheck}
                    label={t('Geverifieerd')}
                    value={verified ? t('Ja') : t('In afwachting')}
                />
                <ProfileRow
                    icon={Calendar}
                    label={t('Lid sinds')}
                    value={new Date(user.created_at).toLocaleDateString(
                        undefined,
                        { dateStyle: 'long' },
                    )}
                />
            </dl>
        </AdminPanel>
    );
}

function ProfileRow({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-start justify-between gap-4 px-5 py-3.5 sm:px-6">
            <dt className="flex items-center gap-2 text-muted-foreground">
                <Icon className="h-4 w-4 shrink-0 opacity-70" />
                {label}
            </dt>
            <dd className="text-right font-medium break-all">{value}</dd>
        </div>
    );
}
