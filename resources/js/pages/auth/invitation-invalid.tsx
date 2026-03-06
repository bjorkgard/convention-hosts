import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    reason: 'expired' | 'invalid';
};

export default function InvitationInvalid({ reason }: Props) {
    const isExpired = reason === 'expired';

    return (
        <AuthLayout
            title={isExpired ? 'Invitation expired' : 'Invalid invitation'}
            description={
                isExpired
                    ? 'This invitation link has expired. Please contact your convention manager to request a new one.'
                    : 'This invitation link is invalid. It may have been modified or is no longer available.'
            }
        >
            <Head title={isExpired ? 'Invitation expired' : 'Invalid invitation'} />

            <div className="flex flex-col items-center gap-6">
                <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                    {isExpired ? (
                        <Clock className="size-8 text-muted-foreground" aria-hidden="true" />
                    ) : (
                        <AlertTriangle className="size-8 text-muted-foreground" aria-hidden="true" />
                    )}
                </div>

                <p className="text-center text-sm text-muted-foreground">
                    {isExpired
                        ? 'Invitation links are valid for 24 hours after they are sent.'
                        : 'Please make sure you are using the exact link from your invitation email.'}
                </p>

                <Button asChild className="w-full">
                    <Link href="/">Go to home page</Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
