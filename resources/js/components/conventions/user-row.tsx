import { router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Mail, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { destroy, resendInvitation } from '@/actions/App/Http/Controllers/UserController';
import RoleBadge from '@/components/conventions/role-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Convention } from '@/types/convention';
import type { ConventionUser } from '@/types/user';

interface UserRowProps {
    user: ConventionUser;
    convention: Convention;
    canManage?: boolean;
    onEdit?: (user: ConventionUser) => void;
}

export default function UserRow({ user, convention, canManage = false, onEdit }: UserRowProps) {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [resending, setResending] = useState(false);

    function handleResend() {
        setResending(true);
        router.post(
            resendInvitation.url({ convention: convention.id, user: user.id }),
            {},
            { onFinish: () => setResending(false) },
        );
    }

    function handleDelete() {
        router.delete(destroy.url({ convention: convention.id, user: user.id }));
        setShowDeleteConfirm(false);
    }

    return (
        <>
            <div className="flex items-center justify-between gap-2 border-b px-3 py-3 last:border-b-0 sm:gap-3 sm:px-4">
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <span className="truncate text-sm font-medium">
                            {user.first_name} {user.last_name}
                        </span>
                        {user.email_confirmed ? (
                            <CheckCircle2
                                className="size-4 shrink-0 text-green-500"
                                aria-label="Email confirmed"
                            />
                        ) : (
                            <AlertTriangle
                                className="size-4 shrink-0 text-amber-500"
                                aria-label="Email not confirmed"
                            />
                        )}
                    </div>
                    <span className="text-muted-foreground truncate text-xs">{user.email}</span>
                    {user.roles && user.roles.length > 0 && (
                        <div className="flex flex-wrap gap-1 pt-0.5">
                            {user.roles.map((role) => (
                                <RoleBadge key={role} role={role} />
                            ))}
                        </div>
                    )}
                </div>

                {canManage && (
                    <div className="flex shrink-0 items-center gap-1">
                        {!user.email_confirmed && (
                            <Button
                                variant="ghost"
                                size="icon"
                                disabled={resending}
                                onClick={handleResend}
                                aria-label="Resend invitation"
                            >
                                <Mail className="size-4" />
                            </Button>
                        )}
                        {onEdit && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => onEdit(user)}
                                aria-label={`Edit ${user.first_name} ${user.last_name}`}
                            >
                                <Pencil className="size-4" />
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setShowDeleteConfirm(true)}
                            aria-label={`Delete ${user.first_name} ${user.last_name}`}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                )}
            </div>

            <Dialog open={showDeleteConfirm} onOpenChange={setShowDeleteConfirm}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove {user.first_name} {user.last_name} from this convention?
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline" className="cursor-pointer">Cancel</Button>
                        </DialogClose>
                        <Button variant="destructive" className="cursor-pointer" onClick={handleDelete}>
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
