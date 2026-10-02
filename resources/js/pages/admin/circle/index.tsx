import { Head, Link, router, useForm } from '@inertiajs/react';
import { BookText, Eye, UserRound, UsersRound, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    AdminCustomerPickerSheet,
    type PickerCustomer,
} from '@/components/admin/admin-customer-picker-sheet';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { translateMemberStatus } from '@/lib/circle-member-status';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';
import customers from '@/routes/admin/customers';

interface CircleMember {
    id: string;
    edition: string | null;
    name: string;
    email: string;
    status: string;
    joined_at: string | null;
}

export default function CircleIndex({ members }: { members: CircleMember[] }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [pickerOpen, setPickerOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] =
        useState<PickerCustomer | null>(null);
    const form = useForm(circleRoutes.assign(locale), {
        user_id: '',
        edition_number: '',
    });

    function submitAssign(event: FormEvent) {
        event.preventDefault();

        if (selectedCustomer === null) {
            return;
        }

        form.transform((data) => ({
            user_id: selectedCustomer.id,
            edition_number:
                data.edition_number === '' ? null : Number(data.edition_number),
        }));

        form.submit({
            onSuccess: () => {
                form.reset();
                setSelectedCustomer(null);
            },
        });
    }

    function removeMember(memberId: string) {
        if (!window.confirm(t('Lid verwijderen uit Founding Circle?'))) {
            return;
        }

        router.delete(
            circleRoutes.remove({
                locale,
                member: Number(memberId),
            }).url,
        );
    }

    return (
        <>
            <Head title={t('Founding Circle')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Founding Circle')}
                    description={t(
                        'Bekijk Founding Edition-leden en reserveringen (1–100).',
                    )}
                    icon={UsersRound}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.register(locale)}>
                            <BookText className="h-4 w-4" /> {t('Naamregister')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form
                    onSubmit={submitAssign}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-end"
                >
                    <div className="min-w-0 flex-1 space-y-1">
                        <p className="text-sm font-medium">
                            {t('Lid toevoegen')}
                        </p>
                        {selectedCustomer ? (
                            <div className="flex min-h-9 items-center gap-3 rounded-md border px-3 py-2">
                                <UserRound className="size-4 shrink-0 text-muted-foreground" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {selectedCustomer.name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {selectedCustomer.email}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 shrink-0"
                                    onClick={() => setSelectedCustomer(null)}
                                    aria-label={t('Wissen')}
                                >
                                    <X className="size-4" />
                                </Button>
                            </div>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full justify-start"
                                onClick={() => setPickerOpen(true)}
                            >
                                <UserRound className="size-4" />
                                {t('Klant selecteren')}
                            </Button>
                        )}
                        {form.errors.user_id && (
                            <p className="text-sm text-destructive">
                                {form.errors.user_id}
                            </p>
                        )}
                    </div>
                    <div className="w-full space-y-1 sm:w-28">
                        <Input
                            type="number"
                            min={1}
                            max={100}
                            placeholder={t('Nummer')}
                            value={form.data.edition_number}
                            onChange={(event) =>
                                form.setData(
                                    'edition_number',
                                    event.target.value,
                                )
                            }
                            required
                        />
                        {form.errors.edition_number && (
                            <p className="text-sm text-destructive">
                                {form.errors.edition_number}
                            </p>
                        )}
                    </div>
                    <Button
                        type="submit"
                        disabled={
                            form.processing || selectedCustomer === null
                        }
                    >
                        {t('Toewijzen')}
                    </Button>
                </form>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Editie')}</TableHead>
                                <TableHead>{t('Lid')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Ingeschreven op')}
                                </TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell className="font-medium">
                                        {member.edition
                                            ? `№ ${member.edition}`
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Link
                                            href={customers.show({
                                                locale,
                                                user: Number(member.id),
                                            })}
                                            className="block min-w-0"
                                        >
                                            <span className="font-medium text-foreground underline-offset-2 hover:underline">
                                                {member.name}
                                            </span>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {member.email}
                                            </p>
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {translateMemberStatus(
                                                member.status,
                                                t,
                                            )}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {member.joined_at ?? '—'}
                                    </TableCell>
                                    <TableCell className="space-x-1 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={circleRoutes.show({
                                                    locale,
                                                    member: Number(member.id),
                                                })}
                                                title={t('Lid bekijken')}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            type="button"
                                            onClick={() =>
                                                removeMember(member.id)
                                            }
                                        >
                                            {t('Verwijderen')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <AdminCustomerPickerSheet
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                title={t('Lid toevoegen')}
                description={t(
                    'Zoek op naam of e-mail en kies een klantaccount.',
                )}
                selectedId={selectedCustomer?.id ?? null}
                onSelect={setSelectedCustomer}
            />
        </>
    );
}

CircleIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Founding Circle',
            href: circleRoutes.index(wayfinderLocale()),
        },
    ],
};
