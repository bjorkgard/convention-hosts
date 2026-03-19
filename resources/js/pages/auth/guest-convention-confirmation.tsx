import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';

interface GuestConventionConfirmationProps {
    conventionName: string;
    email: string;
}

export default function GuestConventionConfirmation({
    conventionName,
    email,
}: GuestConventionConfirmationProps) {
    const { t } = useTranslation();

    return (
        <AuthLayout
            title={t('auth.guest_confirmation.title')}
            description={t('auth.guest_confirmation.description')}
        >
            <Head title={t('auth.guest_confirmation.title')} />

            <div className="space-y-4 text-center">
                <p className="text-sm text-muted-foreground">
                    {t('auth.guest_confirmation.convention_created', { name: conventionName })}
                </p>
                <p className="text-sm text-muted-foreground">
                    {t('auth.guest_confirmation.verification_sent', { email })}
                </p>
                <p className="text-sm text-muted-foreground">
                    {t('auth.guest_confirmation.click_link')}
                </p>
            </div>
        </AuthLayout>
    );
}
