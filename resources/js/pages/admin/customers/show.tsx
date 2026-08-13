import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    IdCard,
    Mail,
    Pencil,
    ShieldCheck,
} from 'lucide-react';
import { motion } from 'motion/react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';
import { avatarUrl } from '@/types/admin';
import type { AdminUser } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

export default function ShowCustomer({ customer }: { customer: AdminUser }) {
    const { t } = useTranslation();
    const { can } = usePermission();
    const url = avatarUrl(customer.avatar);

    return (
        <>
            <Head title={customer.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Klantenprofiel')} icon={IdCard}>
                    <Button variant="outline" asChild>
                        <Link href={customers.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    {can(PERMISSIONS.USERS.EDIT) && (
                        <Button asChild>
                            <Link
                                href={customers.edit({
                                    locale: wayfinderLocale(),
                                    user: customer.id,
                                })}
                            >
                                <Pencil className="h-4 w-4" />{' '}
                                {t('Klant bewerken')}
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>
                <motion.div
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="max-w-2xl overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div className="flex flex-col items-center gap-3 border-b bg-muted/40 p-6 text-center">
                        <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-primary/20 bg-primary/10 text-2xl font-bold text-primary">
                            {url ? (
                                <img
                                    src={url}
                                    alt={customer.name}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                customer.name.charAt(0).toUpperCase()
                            )}
                        </div>
                        <div>
                            <h2 className="text-lg font-bold">
                                {customer.name}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {customer.email}
                            </p>
                        </div>
                    </div>
                    <dl className="divide-y text-sm">
                        <Row
                            icon={IdCard}
                            label={t('Klant-ID')}
                            value={`#${customer.id}`}
                        />
                        <Row
                            icon={Mail}
                            label={t('E-mail')}
                            value={customer.email}
                        />
                        <Row
                            icon={ShieldCheck}
                            label={t('Geverifieerd')}
                            value={
                                customer.email_verified_at
                                    ? t('Ja')
                                    : t('In afwachting')
                            }
                        />
                        <Row
                            icon={Calendar}
                            label={t('Lid sinds')}
                            value={new Date(
                                customer.created_at,
                            ).toLocaleDateString(undefined, {
                                dateStyle: 'long',
                            })}
                        />
                    </dl>
                </motion.div>
            </div>
        </>
    );
}

function Row({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof IdCard;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-center justify-between gap-2 px-6 py-3">
            <dt className="flex items-center gap-2 text-muted-foreground">
                <Icon className="h-4 w-4 opacity-70" /> {label}
            </dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

ShowCustomer.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Klanten', href: customers.index(wayfinderLocale()) },
        { title: 'Profiel', href: customers.index(wayfinderLocale()) },
    ],
};
