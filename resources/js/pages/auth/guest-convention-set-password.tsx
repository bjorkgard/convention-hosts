import { Form, Head } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Auth/GuestConventionVerificationController';
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

const PASSWORD_CRITERIA = [
    { key: 'minLength', label: 'At least 8 characters', test: (p: string) => p.length >= 8 },
    { key: 'lowercase', label: 'At least one lowercase letter', test: (p: string) => /[a-z]/.test(p) },
    { key: 'uppercase', label: 'At least one uppercase letter', test: (p: string) => /[A-Z]/.test(p) },
    { key: 'number', label: 'At least one number', test: (p: string) => /[0-9]/.test(p) },
    { key: 'symbol', label: 'At least one symbol (@$!%*#?&)', test: (p: string) => /[@$!%*#?&]/.test(p) },
] as const;

export default function GuestConventionSetPassword({ user, convention }: Props) {
    const [password, setPassword] = useState('');

    const criteriaResults = useMemo(
        () => PASSWORD_CRITERIA.map((c) => ({ ...c, met: c.test(password) })),
        [password],
    );

    return (
        <AuthLayout
            title={`Welcome, ${user.first_name}!`}
            description={`Set your password to access ${convention.name}`}
        >
            <Head title="Set your password" />

            <Form
                {...store.form({ user: user.id, convention: convention.id })}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={user.email}
                                readOnly
                                tabIndex={-1}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="new-password"
                                placeholder="Password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                tabIndex={2}
                                autoComplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        {password.length > 0 && (
                            <ul className="grid gap-1.5 text-sm" aria-label="Password requirements">
                                {criteriaResults.map((c) => (
                                    <li
                                        key={c.key}
                                        className={`flex items-center gap-2 ${c.met ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`}
                                    >
                                        {c.met ? (
                                            <Check className="size-4 shrink-0" aria-hidden="true" />
                                        ) : (
                                            <X className="size-4 shrink-0" aria-hidden="true" />
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
                            Set password
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
