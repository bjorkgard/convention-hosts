import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    reason: 'expired' | 'invalid';
};

export default function InvitationInvalid({ reason }: Props) {
    const { t } = useTranslation();
    const isExpired = reason === 'expired';

    const title = isExpired
        ? t('auth.invitation_invalid.expired_title')
        : t('auth.invitation_invalid.invalid_title');

    const description = isExpired
        ? t('auth.invitation_invalid.expired_description')
        : t('auth.invitation_invalid.invalid_description');

    const notice = isExpired
        ? t('auth.invitation_invalid.expired_notice')
        : t('auth.invitation_invalid.invalid_notice');

    return (
        <AuthLayout title={title} description={description}>
            <Head title={title} />

            <div className="flex flex-col items-center gap-6">
                <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                    {isExpired ? (
                        <Clock
                            className="size-8 text-muted-foreground"
                            aria-hidden="true"
                        />
                    ) : (
                        <AlertTriangle
                            className="size-8 text-muted-foreground"
                            aria-hidden="true"
                        />
                    )}
                </div>

                <p className="text-center text-sm text-muted-foreground">
                    {notice}
                </p>

                <Button asChild className="w-full">
                    <Link href="/">
                        {t('auth.invitation_invalid.home_link')}
                    </Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
