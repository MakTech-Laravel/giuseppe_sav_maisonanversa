import type { UrlMethodPair } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { Loader2, Save, UserPlus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import FileUpload from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { avatarUrl } from '@/types/admin';
import { AdminPanel } from './admin-resource-shell';

interface UserFormDefaults {
    name: string;
    email: string;
}

interface UserFormProps {
    action: UrlMethodPair;
    submitLabel?: string;
    isEdit?: boolean;
    currentAvatar?: string | null;
    defaults?: UserFormDefaults;
    onCancel?: () => void;
}

export function UserForm({
    action,
    submitLabel,
    isEdit = false,
    currentAvatar = null,
    defaults,
    onCancel,
}: UserFormProps) {
    const { t } = useTranslation();

    const form = useForm(action, {
        name: defaults?.name ?? '',
        email: defaults?.email ?? '',
        password: '',
        avatar: null as File | null,
        remove_avatar: false as boolean,
    });

    const existingAvatar =
        isEdit && currentAvatar && !form.data.avatar && !form.data.remove_avatar
            ? [
                  {
                      id: 'current',
                      path: currentAvatar,
                      url: avatarUrl(currentAvatar) ?? '',
                      mime_type: 'image/*',
                  },
              ]
            : [];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => {
                if (!isEdit) {
                    form.reset();
                }
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <AdminPanel
                title={t('Profielfoto')}
                description={t(
                    'Optioneel. Wordt getoond in community en ledengebied.',
                )}
            >
                <FileUpload
                    accept="image/*"
                    maxSize={2}
                    value={form.data.avatar}
                    onChange={(file) => {
                        form.setData('avatar', (file as File | null) ?? null);
                        form.setData('remove_avatar', false);
                    }}
                    existingFiles={existingAvatar}
                    onRemoveExisting={() => form.setData('remove_avatar', true)}
                    placeholder={t(
                        'Sleep een avatar hierheen of klik om te bladeren',
                    )}
                    hint={t('PNG, JPG of WEBP')}
                    error={form.errors.avatar}
                    classNames={{ wrapper: 'max-w-md' }}
                />
            </AdminPanel>

            <AdminPanel
                title={t('Accountgegevens')}
                description={t('Naam, e-mail en wachtwoord voor dit account.')}
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('Volledige naam')}</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            onBlur={() => form.validate('name')}
                            aria-invalid={form.invalid('name')}
                            placeholder="Jane Doe"
                            autoComplete="name"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('E-mailadres')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                            onBlur={() => form.validate('email')}
                            aria-invalid={form.invalid('email')}
                            placeholder="jane@example.com"
                            autoComplete="email"
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2 sm:max-w-md">
                        <Label htmlFor="password">
                            {t('Wachtwoord')}
                            {isEdit && (
                                <span className="ml-1 text-xs font-normal text-muted-foreground">
                                    {t('(laat leeg om huidige te behouden)')}
                                </span>
                            )}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) =>
                                form.setData('password', e.target.value)
                            }
                            onBlur={() => form.validate('password')}
                            aria-invalid={form.invalid('password')}
                            placeholder="••••••••"
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>
                </div>
            </AdminPanel>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : isEdit ? (
                        <Save className="h-4 w-4" />
                    ) : (
                        <UserPlus className="h-4 w-4" />
                    )}
                    {submitLabel ??
                        (isEdit
                            ? t('Wijzigingen opslaan')
                            : t('Gebruiker aanmaken'))}
                </Button>
                {onCancel && (
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onCancel}
                        disabled={form.processing}
                    >
                        {t('Annuleren')}
                    </Button>
                )}
                {form.validating && (
                    <Badge variant="secondary" className="gap-1.5">
                        <Loader2 className="h-3 w-3 animate-spin" />{' '}
                        {t('Valideren…')}
                    </Badge>
                )}
            </div>
        </form>
    );
}

interface UserFormAsideProps {
    isEdit?: boolean;
    entityLabel: string;
}

export function UserFormAside({
    isEdit = false,
    entityLabel,
}: UserFormAsideProps) {
    const { t } = useTranslation();

    return (
        <AdminPanel title={t('Tips')}>
            <ul className="space-y-3 text-sm text-muted-foreground">
                <li>
                    {isEdit
                        ? t(
                              'Laat het wachtwoord leeg om het huidige wachtwoord te behouden.',
                          )
                        : t(
                              'Het nieuwe account kan direct inloggen met het opgegeven wachtwoord.',
                          )}
                </li>
                <li>
                    {t(
                        'Een avatar is optioneel en wordt getoond in community en ledengebied.',
                    )}
                </li>
                <li>
                    {isEdit
                        ? t('U bewerkt: {{entity}}', { entity: entityLabel })
                        : t('Type account: {{entity}}', {
                              entity: entityLabel,
                          })}
                </li>
            </ul>
        </AdminPanel>
    );
}
