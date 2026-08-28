import { Link, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ArrowLeft, Eye, EyeOff, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import type { InquiryDetails, InquiryRouteHelpers } from '@/types/inquiry';

const META_LABELS: Record<string, string> = {
    datum: 'Datum',
    moment: 'Voorkeur moment',
    soort: 'Soort',
    ervaring: 'Uw ervaring',
    club_name: 'Club',
    location: 'Locatie',
    courts: 'Courts',
    format: 'Formaat',
};

function Field({
    label,
    value,
    mono = false,
    pre = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
    pre?: boolean;
}) {
    return (
        <div className="grid min-w-0 gap-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'wrap-break-word text-sm font-medium',
                    mono && 'font-mono tabular-nums',
                    pre && 'whitespace-pre-wrap',
                )}
            >
                {value}
            </p>
        </div>
    );
}

function metaValue(value: unknown): string {
    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    if (value === null || value === undefined) {
        return '—';
    }

    return JSON.stringify(value);
}

export function InquiryShow({
    title,
    description,
    icon: Icon,
    inquiry,
    routes,
}: {
    title: string;
    description: string;
    icon: LucideIcon;
    inquiry: InquiryDetails;
    routes: InquiryRouteHelpers;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const metaEntries = Object.entries(inquiry.meta ?? {});

    function toggleSeen() {
        router.patch(
            routes.seen({
                locale,
                inquiry: Number(inquiry.id),
            }).url,
            { seen: !inquiry.seen },
            { preserveScroll: true },
        );
    }

    return (
        <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <AdminPageHeader
                title={title}
                description={description}
                icon={Icon}
            >
                <Button variant="outline" asChild>
                    <Link href={routes.index(locale)}>
                        <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                    </Link>
                </Button>
            </AdminPageHeader>

            <AdminResourceShell
                aside={
                    <AdminPanel
                        title={t('Acties')}
                        description={t(
                            'Markeer deze aanvraag of ga terug naar de lijst.',
                        )}
                    >
                        <div className="flex flex-col gap-2">
                            <Button
                                variant="outline"
                                className="w-full"
                                onClick={toggleSeen}
                            >
                                {inquiry.seen ? (
                                    <EyeOff className="h-4 w-4" />
                                ) : (
                                    <Eye className="h-4 w-4" />
                                )}
                                {inquiry.seen
                                    ? t('Ongelezen')
                                    : t('Gezien')}
                            </Button>
                            <Button variant="outline" asChild className="w-full">
                                <Link href={routes.index(locale)}>
                                    <ArrowLeft className="h-4 w-4" />{' '}
                                    {t('Terug')}
                                </Link>
                            </Button>
                            <ConfirmDeleteDialog
                                description={t(
                                    'Deze aanvraag wordt permanent verwijderd.',
                                )}
                                onConfirm={() =>
                                    router.delete(
                                        routes.destroy({
                                            locale,
                                            inquiry: Number(inquiry.id),
                                        }).url,
                                    )
                                }
                            >
                                <Button
                                    variant="destructive"
                                    className="w-full"
                                >
                                    <Trash2 className="h-4 w-4" />{' '}
                                    {t('Verwijderen')}
                                </Button>
                            </ConfirmDeleteDialog>
                        </div>
                    </AdminPanel>
                }
            >
                <AdminPanel
                    title={t('Aanvraag')}
                    description={t('Inzending zoals ontvangen.')}
                >
                    <div className="mb-5 flex flex-wrap gap-2">
                        <Badge variant="secondary">
                            {t(inquiry.type_label)}
                        </Badge>
                        <Badge
                            variant={inquiry.seen ? 'secondary' : 'default'}
                        >
                            {inquiry.seen ? t('Gezien') : t('Ongelezen')}
                        </Badge>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label={t('Naam')} value={inquiry.name} />
                        <Field label={t('E-mail')} value={inquiry.email} />
                        <Field
                            label={t('Telefoon')}
                            value={inquiry.phone || '—'}
                        />
                        <Field
                            label={t('Locale')}
                            value={inquiry.locale.toUpperCase()}
                            mono
                        />
                        <Field
                            label={t('Datum')}
                            value={
                                inquiry.created_at
                                    ? new Date(
                                          inquiry.created_at,
                                      ).toLocaleString()
                                    : '—'
                            }
                        />
                        <Field label={t('IP')} value={inquiry.ip || '—'} mono />
                    </div>
                    {inquiry.subject ? (
                        <div className="mt-5">
                            <Field
                                label={t('Onderwerp')}
                                value={inquiry.subject}
                            />
                        </div>
                    ) : null}
                    <div className="mt-5">
                        <Field
                            label={t('Bericht')}
                            value={inquiry.message || '—'}
                            pre
                        />
                    </div>
                    {metaEntries.length > 0 ? (
                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            {metaEntries.map(([key, value]) => (
                                <Field
                                    key={key}
                                    label={t(META_LABELS[key] ?? key)}
                                    value={t(metaValue(value))}
                                />
                            ))}
                        </div>
                    ) : null}
                </AdminPanel>
            </AdminResourceShell>
        </div>
    );
}
