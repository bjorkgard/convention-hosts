import { Form, Head } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { store } from '@/actions/App/Http/Controllers/Auth/InvitationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
    convention: {
        id: number;
        name: string;
    };
};

export default function Invitation({ user, convention }: Props) {
    const { t } = useTranslation();
    const [password, setPassword] = useState('');

    const passwordCriteria = useMemo(
        () => [
            {
                key: 'minLength',
                label: t('auth.password_criteria.min_length'),
                test: (p: string) => p.length >= 8,
            },
            {
                key: 'lowercase',
                label: t('auth.password_criteria.lowercase'),
                test: (p: string) => /[a-z]/.test(p),
            },
            {
                key: 'uppercase',
                label: t('auth.password_criteria.uppercase'),
                test: (p: string) => /[A-Z]/.test(p),
            },
            {
                key: 'number',
                label: t('auth.password_criteria.number'),
                test: (p: string) => /[0-9]/.test(p),
            },
            {
                key: 'symbol',
                label: t('auth.password_criteria.symbol'),
                test: (p: string) => /[@$!%*#?&]/.test(p),
            },
        ],
        [t],
    );

    const criteriaResults = useMemo(
        () => passwordCriteria.map((c) => ({ ...c, met: c.test(password) })),
        [password, passwordCriteria],
    );

    return (
        <AuthLayout
            title={t('auth.invitation.welcome_title', {
                name: user.first_name,
            })}
            description={t('auth.invitation.description', {
                convention: convention.name,
            })}
        >
            <Head title={t('auth.invitation.title')} />

            <Form
                {...store.form({
                    user: String(user.id),
                    convention: String(convention.id),
                })}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                {t('auth.invitation.email_label')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={user.email}
                                readOnly
                                tabIndex={-1}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('auth.invitation.password_label')}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="new-password"
                                placeholder={t(
                                    'auth.invitation.password_placeholder',
                                )}
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('auth.invitation.confirm_password_label')}
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                tabIndex={2}
                                autoComplete="new-password"
                                placeholder={t(
                                    'auth.invitation.confirm_password_placeholder',
                                )}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        {password.length > 0 && (
                            <ul
                                className="grid gap-1.5 text-sm"
                                aria-label={t('auth.password_criteria.label')}
                            >
                                {criteriaResults.map((c) => (
                                    <li
                                        key={c.key}
                                        className={`flex items-center gap-2 ${c.met ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`}
                                    >
                                        {c.met ? (
                                            <Check
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <X
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {c.label}
                                    </li>
                                ))}
                            </ul>
                        )}

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            tabIndex={3}
                            disabled={processing}
                            data-test="set-password-button"
                        >
                            {processing && <Spinner />}
                            {t('auth.invitation.submit')}
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
