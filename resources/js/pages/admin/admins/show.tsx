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
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';
import { avatarUrl } from '@/types/admin';
import type { AdminUser } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

export default function ShowAdmin({ user }: { user: AdminUser }) {
    const { can } = usePermission();
    const url = avatarUrl(user.avatar);

    return (
        <>
            <Head title={user.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title="Administrator profile" icon={IdCard}>
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back
                        </Link>
                    </Button>
                    {can(PERMISSIONS.USERS.EDIT) && (
                        <Button asChild>
                            <Link
                                href={admins.edit({
                                    locale: wayfinderLocale(),
                                    user: user.id,
                                })}
                            >
                                <Pencil className="h-4 w-4" /> Edit
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
                                    alt={user.name}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                user.name.charAt(0).toUpperCase()
                            )}
                        </div>
                        <div>
                            <h2 className="text-lg font-bold">{user.name}</h2>
                            <p className="text-sm text-muted-foreground">
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <dl className="divide-y text-sm">
                        <Row
                            icon={IdCard}
                            label="Administrator ID"
                            value={`#${user.id}`}
                        />
                        <Row icon={Mail} label="Email" value={user.email} />
                        <Row
                            icon={ShieldCheck}
                            label="Verified"
                            value={user.email_verified_at ? 'Yes' : 'Pending'}
                        />
                        <Row
                            icon={Calendar}
                            label="Joined"
                            value={new Date(user.created_at).toLocaleDateString(
                                undefined,
                                { dateStyle: 'long' },
                            )}
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

ShowAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Beheerder', href: admins.index(wayfinderLocale()) },
        { title: 'Profiel', href: admins.index(wayfinderLocale()) },
    ],
};
