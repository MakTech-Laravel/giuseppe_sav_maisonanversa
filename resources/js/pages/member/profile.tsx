import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';

export default function MemberProfile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Profile & Account" />
            <MemberPageHeader
                eyebrow="Account"
                title="Profile & account"
                description="Your name, username and email. Username is how you can sign in."
            />

            <MemberPanel className="max-w-xl space-y-8">
                <Form
                    action="/member/profile"
                    method="patch"
                    options={{ preserveScroll: true }}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={auth.user.name}
                                    required
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    name="username"
                                    defaultValue={auth.user.username}
                                    required
                                    autoComplete="username"
                                />
                                <InputError message={errors.username} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    defaultValue={auth.user.email}
                                    required
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <p className="text-sm text-choc3">
                                        Your email is unverified.{' '}
                                        <Link
                                            href={send()}
                                            className="text-gold2 underline"
                                        >
                                            Resend verification
                                        </Link>
                                    </p>
                                )}

                            {status === 'verification-link-sent' && (
                                <p className="text-sm text-gold2">
                                    A new verification link has been sent.
                                </p>
                            )}

                            <Button disabled={processing}>Save profile</Button>
                        </>
                    )}
                </Form>

                <DeleteMemberAccount />
            </MemberPanel>
        </>
    );
}

function DeleteMemberAccount() {
    const [confirming, setConfirming] = useState(false);

    return (
        <div className="border-t border-gold/20 pt-8">
            <h2 className="font-serif text-[22px] text-choc">Delete account</h2>
            <p className="mt-2 text-[14px] text-choc3">
                Permanently remove your account and data from Maison Anversa.
            </p>

            {!confirming ? (
                <Button
                    type="button"
                    variant="destructive"
                    className="mt-4"
                    onClick={() => setConfirming(true)}
                >
                    Delete account
                </Button>
            ) : (
                <Form
                    action="/member/profile"
                    method="delete"
                    className="mt-4 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Confirm with password
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex gap-3">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setConfirming(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Confirm delete
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
