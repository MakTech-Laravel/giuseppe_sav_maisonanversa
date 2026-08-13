import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useRef, useState  } from 'react';
import type {ReactNode} from 'react';
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
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Profile settings" />

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
                            title="Profile photo"
                            description="This image appears in the sidebar and across the admin area."
                        >
                            <ProfileAvatarField
                                name={auth.user.name}
                                email={auth.user.email}
                                avatarUrl={auth.user.avatar_url}
                                error={errors.avatar}
                            />
                        </SettingsPanel>

                        <SettingsPanel
                            title="Profile information"
                            description="Update your name, username, and email address."
                        >
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        className="w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="username">Username</Label>
                                    <Input
                                        id="username"
                                        className="w-full"
                                        defaultValue={auth.user.username}
                                        name="username"
                                        required
                                        autoComplete="username"
                                        placeholder="username"
                                    />
                                    <InputError message={errors.username} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="email"
                                        placeholder="Email address"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div className="mt-5 rounded-md border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                                        Your email address is unverified.{' '}
                                        <Link
                                            href={send()}
                                            as="button"
                                            className="font-medium text-primary underline underline-offset-4"
                                        >
                                            Resend verification email
                                        </Link>
                                        {status ===
                                            'verification-link-sent' && (
                                            <p className="mt-2 font-medium text-primary">
                                                A new verification link has been
                                                sent to your email address.
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
                                    Save changes
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
                        {displayUrl ? 'Change photo' : 'Upload photo'}
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
                            Remove
                        </Button>
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    PNG, JPG, or WEBP · max 2 MB
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
