import { Head } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';

interface GuestConventionConfirmationProps {
    conventionName: string;
    email: string;
}

export default function GuestConventionConfirmation({
    conventionName,
    email,
}: GuestConventionConfirmationProps) {
    return (
        <AuthLayout
            title="Convention created"
            description="Your convention has been created. Please check your email to set your password."
        >
            <Head title="Convention created" />

            <div className="space-y-4 text-center">
                <p className="text-sm text-muted-foreground">
                    Your convention <strong>{conventionName}</strong> has been
                    created successfully.
                </p>
                <p className="text-sm text-muted-foreground">
                    A verification email has been sent to{' '}
                    <strong>{email}</strong>.
                </p>
                <p className="text-sm text-muted-foreground">
                    Please click the link in the email to set your password and
                    activate your account.
                </p>
            </div>
        </AuthLayout>
    );
}
