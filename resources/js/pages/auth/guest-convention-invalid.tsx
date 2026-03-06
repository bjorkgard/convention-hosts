import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    reason: 'expired' | 'invalid';
};

export default function GuestConventionInvalid({ reason }: Props) {
    const isExpired = reason === 'expired';

    return (
        <AuthLayout
            title={isExpired ? 'Verification link expired' : 'Invalid verification link'}
            description={
                isExpired
                    ? 'This verification link has expired. Please create a new convention to receive a new link.'
                    : 'This verification link is invalid. The link may have been modified or is no longer valid.'
            }
        >
            <Head title={isExpired ? 'Verification link expired' : 'Invalid verification link'} />

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
                        ? 'Verification links are valid for 24 hours after they are sent.'
                        : 'Please make sure you are using the exact link from your verification email.'}
                </p>

                <Button asChild className="w-full">
                    <Link href="/">Go to home page</Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
