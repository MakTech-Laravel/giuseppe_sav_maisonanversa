import { Head, Link, router } from '@inertiajs/react';
import { Check, ClipboardList, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import FoundingCircleClaimController from '@/actions/App/Http/Controllers/Admin/FoundingCircleClaimController';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';

type ClaimRow = {
    id: string;
    user: { id: string; name: string; email: string };
    edition_number: string;
    racket_label: string;
    product_name: string | null;
    status: string;
    status_label: string;
    source: string;
    source_label: string;
    admin_note: string | null;
    reviewed_by: string | null;
    created_at: string | null;
    reviewed_at: string | null;
};

type StatusOption = { value: string; label: string };

export default function CircleClaims({
    claims,
    pendingCount,
    filters,
    statusOptions,
}: {
    claims: ClaimRow[];
    pendingCount: number;
    filters: { status: string };
    statusOptions: StatusOption[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [rejectNotes, setRejectNotes] = useState<Record<string, string>>({});

    function setStatusFilter(status: string) {
        router.get(
            FoundingCircleClaimController.index.url(locale),
            { status: status === 'all' ? undefined : status },
            { preserveState: true, replace: true },
        );
    }

    function approve(claimId: string) {
        router.post(
            FoundingCircleClaimController.approve.url({
                locale,
                claim: Number(claimId),
            }),
        );
    }

    function reject(claimId: string) {
        router.post(
            FoundingCircleClaimController.reject.url({
                locale,
                claim: Number(claimId),
            }),
            { admin_note: rejectNotes[claimId] || null },
        );
    }

    return (
        <>
            <Head title={t('Racketregistraties')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Racketregistraties')}
                    description={t(
                        'Keur serienummerregistraties goed of af voordat Founding Circle wordt geactiveerd.',
                    )}
                    icon={ClipboardList}
                    breadcrumbs={[
                        {
                            title: t('Dashboard'),
                            href: dashboard(locale).url,
                        },
                        {
                            title: t('Founding Circle'),
                            href: circleRoutes.index(locale).url,
                        },
                        { title: t('Racketregistraties') },
                    ]}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index(locale)}>
                            {t('Leden')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                {pendingCount > 0 && (
                    <p className="text-sm text-muted-foreground">
                        {t('{{count}} in afwachting', {
                            count: pendingCount,
                        })}
                    </p>
                )}

                <div className="flex max-w-xs flex-col gap-2">
                    <Select
                        value={filters.status}
                        onValueChange={setStatusFilter}
                    >
                        <SelectTrigger aria-label={t('Status')}>
                            <SelectValue placeholder={t('Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Lid')}</TableHead>
                                <TableHead>{t('Racket')}</TableHead>
                                <TableHead>{t('Bron')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {claims.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t('Geen registraties gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                claims.map((claim) => (
                                    <TableRow key={claim.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {claim.user.name}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {claim.user.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium">
                                                {claim.racket_label}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {claim.product_name}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {t(claim.source_label)}
                                            {claim.created_at
                                                ? ` · ${claim.created_at}`
                                                : ''}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    claim.status === 'approved'
                                                        ? 'default'
                                                        : claim.status ===
                                                            'rejected'
                                                          ? 'destructive'
                                                          : 'secondary'
                                                }
                                            >
                                                {t(claim.status_label)}
                                            </Badge>
                                            {claim.admin_note && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {claim.admin_note}
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {claim.status === 'pending' ? (
                                                <div className="flex min-w-56 flex-col gap-2">
                                                    <div className="flex gap-2">
                                                        <Button
                                                            size="sm"
                                                            type="button"
                                                            onClick={() =>
                                                                approve(
                                                                    claim.id,
                                                                )
                                                            }
                                                        >
                                                            <Check className="h-4 w-4" />
                                                            {t('Goedkeuren')}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                reject(claim.id)
                                                            }
                                                        >
                                                            <X className="h-4 w-4" />
                                                            {t('Afwijzen')}
                                                        </Button>
                                                    </div>
                                                    <Input
                                                        value={
                                                            rejectNotes[
                                                                claim.id
                                                            ] ?? ''
                                                        }
                                                        onChange={(event) =>
                                                            setRejectNotes(
                                                                (current) => ({
                                                                    ...current,
                                                                    [claim.id]:
                                                                        event
                                                                            .target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                        placeholder={t(
                                                            'Notitie bij afwijzing (optioneel)',
                                                        )}
                                                    />
                                                </div>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    {claim.reviewed_by
                                                        ? `${claim.reviewed_by}${claim.reviewed_at ? ` · ${claim.reviewed_at}` : ''}`
                                                        : '—'}
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}
