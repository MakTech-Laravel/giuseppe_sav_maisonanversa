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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { send } from '@/routes/verification';
import type { User } from '@/types/auth';

export default function MemberProfile({
    mustVerifyEmail,
    status,
    genders,
    preferredLocale,
    showFirstNameOnCards = true,
    locales,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    genders: { value: string; label: string }[];
    preferredLocale: string;
    showFirstNameOnCards?: boolean;
    locales: { value: string; label: string }[];
}) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const user = auth.user;

    if (!user) {
        return null;
    }

    return (
        <>
            <Head title={t('Profile & account')} />
            <MemberPageHeader
                eyebrow={t('Account')}
                title={t('Profile & account')}
                description={t(
                    'Manage how you appear in the house — photo, name, and how you sign in.',
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
                                title={t('Identity')}
                                description={t(
                                    'Your photo appears in the member header and community surfaces.',
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
                                title={t('Account details')}
                                description={t(
                                    'Username is an alternate way to sign in alongside email.',
                                )}
                            />
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="first_name"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('First name')}
                                    </Label>
                                    <Input
                                        id="first_name"
                                        name="first_name"
                                        defaultValue={profileFirstName(user)}
                                        required
                                        autoComplete="given-name"
                                        className={memberFieldClassName}
                                    />
                                    <InputError message={errors.first_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="last_name"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Last name')}
                                    </Label>
                                    <Input
                                        id="last_name"
                                        name="last_name"
                                        defaultValue={profileLastName(user)}
                                        autoComplete="family-name"
                                        className={memberFieldClassName}
                                    />
                                    <InputError message={errors.last_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="username"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Username')}
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
                                        {t('Gender')}
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
                                        htmlFor="locale"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Language for emails and notifications')}
                                    </Label>
                                    <PreferredLanguageSelect
                                        defaultValue={preferredLocale}
                                        options={locales}
                                        triggerClassName={cn(
                                            memberFieldClassName,
                                            'rounded-none shadow-none [&_svg]:text-sand',
                                        )}
                                    />
                                    <p className="font-sans text-[11px] text-stone">
                                        {t(
                                            'Session emails and notifications are sent in this language.',
                                        )}
                                    </p>
                                    <InputError message={errors.locale} />
                                </div>

                                <MatchCardNameField
                                    defaultChecked={showFirstNameOnCards}
                                    error={errors.show_first_name_on_cards}
                                />

                                <div className="grid gap-2 md:col-span-2">
                                    <Label
                                        htmlFor="email"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Email')}
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
                                        {t('Your email is unverified.')}{' '}
                                        <Link
                                            href={send()}
                                            className="text-gold underline"
                                        >
                                            {t('Resend verification')}
                                        </Link>
                                    </p>
                                )}

                            {status === 'verification-link-sent' && (
                                <p className="mt-4 text-sm text-gold">
                                    {t(
                                        'A new verification link has been sent.',
                                    )}
                                </p>
                            )}

                            <div className="mt-8 flex justify-end border-t border-gold/20 pt-5">
                                <Button
                                    disabled={processing}
                                    className="min-w-40 rounded-none"
                                >
                                    {t('Save profile')}
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
                        {displayUrl ? t('Change photo') : t('Upload photo')}
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
                            {t('Remove')}
                        </Button>
                    )}
                </div>
                <p className="font-sans text-[11px] text-stone">
                    {t('PNG, JPG or WEBP · max 2 MB')}
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

function PreferredLanguageSelect({
    defaultValue,
    options,
    triggerClassName,
}: {
    defaultValue: string;
    options: { value: string; label: string }[];
    triggerClassName?: string;
}) {
    const { t } = useTranslation();
    const [value, setValue] = useState(defaultValue);

    return (
        <>
            <input type="hidden" name="locale" value={value} />
            <Select
                value={value || undefined}
                onValueChange={setValue}
                required
            >
                <SelectTrigger
                    id="locale"
                    aria-label={t('Language for emails and notifications')}
                    className={cn('w-full', triggerClassName)}
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}

/**
 * Match cards print initials plus the first name; members may replace the
 * name with the Private label. Last names never appear on a card.
 */
function MatchCardNameField({
    defaultChecked,
    error,
}: {
    defaultChecked: boolean;
    error?: string;
}) {
    const { t } = useTranslation();
    const [checked, setChecked] = useState(defaultChecked);

    return (
        <div className="grid gap-2 md:col-span-2">
            <input
                type="hidden"
                name="show_first_name_on_cards"
                value={checked ? '1' : '0'}
            />
            <label className="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(event) => setChecked(event.target.checked)}
                    className="mt-0.5 size-4 accent-gold"
                />
                <span className="space-y-1">
                    <span className="block font-sans text-[10px] tracking-[0.16em] text-gold uppercase">
                        {t('Show my first name on match cards')}
                    </span>
                    <span className="block font-sans text-[11px] text-stone">
                        {t(
                            'If this is off, “Private” is shown instead of your first name.',
                        )}
                    </span>
                </span>
            </label>
            <InputError message={error} />
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
                title={t('Delete account')}
                description={t(
                    'Permanently remove your account and data from Maison Anversa. This cannot be undone.',
                )}
            />

            {!confirming ? (
                <Button
                    type="button"
                    variant="destructive"
                    className="rounded-none"
                    onClick={() => setConfirming(true)}
                >
                    {t('Delete account')}
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
                                    {t('Confirm with password')}
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
                                    {t('Cancel')}
                                </Button>
                                <Button
                                    variant="destructive"
                                    className="rounded-none"
                                    disabled={processing}
                                >
                                    {t('Confirm delete')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </MemberPanel>
    );
}

function profileFirstName(user: User): string {
    if (typeof user.first_name === 'string' && user.first_name !== '') {
        return user.first_name;
    }

    const parts = user.name.trim().split(/\s+/);

    if (parts.length <= 1) {
        return parts[0] ?? '';
    }

    return parts.slice(0, -1).join(' ');
}

function profileLastName(user: User): string {
    if (typeof user.last_name === 'string') {
        return user.last_name;
    }

    const parts = user.name.trim().split(/\s+/);

    return parts.length <= 1 ? '' : (parts[parts.length - 1] ?? '');
}
