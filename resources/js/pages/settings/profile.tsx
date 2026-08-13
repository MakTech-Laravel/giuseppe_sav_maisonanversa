import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t } = useTranslation();
    const { auth } = usePage().props;

    return (
        <>
            <Head title={t('Profielinstellingen')} />

            <Form
                {...ProfileController.update.form(wayfinderLocale())}
                options={{
                    preserveScroll: true,
                }}
                encType="multipart/form-data"
                className="space-y-8"
            >
                {({ processing, errors }) => (
                    <>
                        <SettingsPanel
                            title={t('Profielfoto')}
                            description={t(
                                'Deze afbeelding verschijnt in de zijbalk en in het beheergebied.',
                            )}
                        >
                            <ProfileAvatarField
                                name={auth.user.name}
                                email={auth.user.email}
                                avatarUrl={auth.user.avatar_url}
                                error={errors.avatar}
                            />
                        </SettingsPanel>

                        <SettingsPanel
                            title={t('Profielgegevens')}
                            description={t(
                                'Werk uw naam, gebruikersnaam en e-mailadres bij.',
                            )}
                        >
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="name">{t('Naam')}</Label>
                                    <Input
                                        id="name"
                                        className="w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder={t('Volledige naam')}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="username">
                                        {t('Gebruikersnaam')}
                                    </Label>
                                    <Input
                                        id="username"
                                        className="w-full"
                                        defaultValue={auth.user.username}
                                        name="username"
                                        required
                                        autoComplete="username"
                                        placeholder={t('gebruikersnaam')}
                                    />
                                    <InputError message={errors.username} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t('E-mailadres')}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="email"
                                        placeholder={t('E-mailadres')}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div className="mt-5 rounded-md border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                                        {t(
                                            'Uw e-mailadres is niet geverifieerd.',
                                        )}{' '}
                                        <Link
                                            href={send()}
                                            as="button"
                                            className="font-medium text-primary underline underline-offset-4"
                                        >
                                            {t(
                                                'Verificatie-e-mail opnieuw versturen',
                                            )}
                                        </Link>
                                        {status ===
                                            'verification-link-sent' && (
                                            <p className="mt-2 font-medium text-primary">
                                                {t(
                                                    'Er is een nieuwe verificatielink naar uw e-mailadres gestuurd.',
                                                )}
                                            </p>
                                        )}
                                    </div>
                                )}

                            <div className="mt-6 flex justify-end border-t border-border pt-5">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                    className="min-w-28"
                                >
                                    {t('Wijzigingen opslaan')}
                                </Button>
                            </div>
                        </SettingsPanel>
                    </>
                )}
            </Form>

            <DeleteUser />
        </>
    );
}

function SettingsPanel({
    title,
    description,
    children,
    className,
}: {
    title: string;
    description: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'rounded-lg border border-border bg-card/60 p-5 shadow-none sm:p-6',
                className,
            )}
        >
            <header className="mb-5 space-y-1">
                <h2 className="text-base font-medium tracking-tight text-foreground">
                    {title}
                </h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </header>
            {children}
        </section>
    );
}

function ProfileAvatarField({
    name,
    email,
    avatarUrl,
    error,
}: {
    name: string;
    email: string;
    avatarUrl?: string | null;
    error?: string;
}) {
    const { t } = useTranslation();
    const getInitials = useInitials();
    const inputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [removeAvatar, setRemoveAvatar] = useState(false);

    const displayUrl = removeAvatar ? null : (preview ?? avatarUrl ?? null);

    return (
        <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
            <Avatar className="size-20 overflow-hidden rounded-full border border-border sm:size-24">
                {displayUrl ? (
                    <AvatarImage src={displayUrl} alt={name} />
                ) : null}
                <AvatarFallback className="rounded-full bg-muted text-lg tracking-wide text-foreground">
                    {getInitials(name)}
                </AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1 space-y-3">
                <div>
                    <p className="truncate text-lg font-medium text-foreground">
                        {name}
                    </p>
                    <p className="truncate text-sm text-muted-foreground">
                        {email}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                    >
                        {displayUrl
                            ? t('Foto wijzigen')
                            : t('Foto uploaden')}
                    </Button>
                    {displayUrl && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setRemoveAvatar(true);
                                setPreview(null);

                                if (inputRef.current) {
                                    inputRef.current.value = '';
                                }
                            }}
                        >
                            {t('Verwijderen')}
                        </Button>
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    {t('PNG, JPG of WEBP · max. 2 MB')}
                </p>
                <InputError message={error} />
            </div>

            <input
                ref={inputRef}
                type="file"
                name="avatar"
                accept="image/*"
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];
                    setRemoveAvatar(false);
                    setPreview(file ? URL.createObjectURL(file) : null);
                }}
            />
            {removeAvatar && (
                <input type="hidden" name="remove_avatar" value="1" />
            )}
        </div>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profielinstellingen',
            href: edit(wayfinderLocale()),
        },
    ],
};
