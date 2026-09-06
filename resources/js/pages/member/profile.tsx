import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    destroy,
    updateProfile,
} from '@/actions/App/Http/Controllers/Member/DashboardController';
import { GenderSelect } from '@/components/gender-select';
import InputError from '@/components/input-error';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    memberFieldClassName,
} from '@/components/member/member-ui';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { send } from '@/routes/verification';

export default function MemberProfile({
    mustVerifyEmail,
    status,
    genders,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    genders: { value: string; label: string }[];
}) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const user = auth.user;

    if (!user) {
        return null;
    }

    return (
        <>
            <Head title={t('Profiel & account')} />
            <MemberPageHeader
                eyebrow={t('Account')}
                title={t('Profiel & account')}
                description={t(
                    'Beheer hoe u in het huis verschijnt — foto, naam en hoe u inlogt.',
                )}
            />

            <Form
                {...updateProfile.form(locale)}
                options={{ preserveScroll: true }}
                encType="multipart/form-data"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <MemberPanel>
                            <MemberSectionTitle
                                title={t('Identiteit')}
                                description={t(
                                    'Uw foto verschijnt in de lidheader en op community-oppervlakken.',
                                )}
                            />
                            <ProfileAvatarField
                                name={user.name}
                                email={user.email}
                                avatarUrl={user.avatar_url}
                                error={errors.avatar}
                            />
                        </MemberPanel>

                        <MemberPanel>
                            <MemberSectionTitle
                                title={t('Accountgegevens')}
                                description={t(
                                    'Gebruikersnaam is een alternatieve manier om in te loggen naast e-mail.',
                                )}
                            />
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Naam')}
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={user.name}
                                        required
                                        autoComplete="name"
                                        className={memberFieldClassName}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="username"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Gebruikersnaam')}
                                    </Label>
                                    <Input
                                        id="username"
                                        defaultValue={user.username}
                                        readOnly
                                        disabled
                                        autoComplete="username"
                                        className={memberFieldClassName}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="gender"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Geslacht')}
                                    </Label>
                                    <GenderSelect
                                        defaultValue={
                                            typeof user.gender === 'string'
                                                ? user.gender
                                                : ''
                                        }
                                        options={genders}
                                        triggerClassName={cn(
                                            memberFieldClassName,
                                            'rounded-none shadow-none [&_svg]:text-sand',
                                        )}
                                    />
                                    <InputError message={errors.gender} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label
                                        htmlFor="email"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('E-mail')}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        defaultValue={user.email}
                                        required
                                        autoComplete="email"
                                        className={memberFieldClassName}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            {mustVerifyEmail &&
                                user.email_verified_at === null && (
                                    <p className="mt-4 text-sm text-sand">
                                        {t('Uw e-mail is niet geverifieerd.')}{' '}
                                        <Link
                                            href={send()}
                                            className="text-gold underline"
                                        >
                                            {t('Verificatie opnieuw versturen')}
                                        </Link>
                                    </p>
                                )}

                            {status === 'verification-link-sent' && (
                                <p className="mt-4 text-sm text-gold">
                                    {t(
                                        'Er is een nieuwe verificatielink verstuurd.',
                                    )}
                                </p>
                            )}

                            <div className="mt-8 flex justify-end border-t border-gold/20 pt-5">
                                <Button
                                    disabled={processing}
                                    className="min-w-40 rounded-none"
                                >
                                    {t('Profiel opslaan')}
                                </Button>
                            </div>
                        </MemberPanel>
                    </>
                )}
            </Form>

            <DeleteMemberAccount locale={locale} />
        </>
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
        <div className="flex flex-col gap-6 sm:flex-row sm:items-center">
            <Avatar className="size-24 overflow-hidden rounded-full border border-gold/40">
                {displayUrl ? (
                    <AvatarImage src={displayUrl} alt={name} />
                ) : null}
                <AvatarFallback className="rounded-full bg-choc font-sans text-[22px] tracking-[0.08em] text-cream">
                    {getInitials(name)}
                </AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1 space-y-3">
                <div>
                    <p className="truncate font-serif text-[22px] text-cream">
                        {name}
                    </p>
                    <p className="truncate font-sans text-[12px] text-sand">
                        {email}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        className="rounded-none"
                        onClick={() => inputRef.current?.click()}
                    >
                        {displayUrl ? t('Foto wijzigen') : t('Foto uploaden')}
                    </Button>
                    {displayUrl && (
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-none border-gold/40 bg-transparent text-sand hover:bg-choc hover:text-cream"
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
                <p className="font-sans text-[11px] text-stone">
                    {t('PNG, JPG of WEBP · max 2 MB')}
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

function DeleteMemberAccount({ locale }: { locale: string }) {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);

    return (
        <MemberPanel
            className={cn(
                'mt-6 border-red-900/40 bg-[#2a1816]',
                confirming && 'border-red-800/60',
            )}
        >
            <MemberSectionTitle
                title={t('Account verwijderen')}
                description={t(
                    'Verwijder uw account en gegevens permanent van Maison Anversa. Dit kan niet ongedaan worden gemaakt.',
                )}
            />

            {!confirming ? (
                <Button
                    type="button"
                    variant="destructive"
                    className="rounded-none"
                    onClick={() => setConfirming(true)}
                >
                    {t('Account verwijderen')}
                </Button>
            ) : (
                <Form {...destroy.form(locale)} className="max-w-md space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                >
                                    {t('Bevestig met wachtwoord')}
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    className={memberFieldClassName}
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-none border-gold/40 bg-transparent text-sand hover:bg-choc hover:text-cream"
                                    onClick={() => setConfirming(false)}
                                >
                                    {t('Annuleren')}
                                </Button>
                                <Button
                                    variant="destructive"
                                    className="rounded-none"
                                    disabled={processing}
                                >
                                    {t('Verwijderen bevestigen')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </MemberPanel>
    );
}
