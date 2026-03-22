import { router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Mail,
    Pencil,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
    destroy,
    resendInvitation,
} from '@/actions/App/Http/Controllers/UserController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import RoleBadge from '@/components/conventions/role-badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Convention } from '@/types/convention';
import type { ConventionUser } from '@/types/user';

interface UserRowProps {
    user: ConventionUser;
    convention: Convention;
    canManage?: boolean;
    onEdit?: (user: ConventionUser) => void;
}

export default function UserRow({
    user,
    convention,
    canManage = false,
    onEdit,
}: UserRowProps) {
    const { t } = useTranslation();
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [resending, setResending] = useState(false);
    const fullName = `${user.first_name} ${user.last_name}`;

    function handleResend() {
        setResending(true);
        router.post(
            resendInvitation.url({ convention: convention.id, user: user.id }),
            {},
            { onFinish: () => setResending(false) },
        );
    }

    function handleDelete() {
        router.delete(
            destroy.url({ convention: convention.id, user: user.id }),
        );
        setShowDeleteConfirm(false);
    }

    return (
        <>
            <div className="flex items-center justify-between gap-2 border-b border-border/50 px-3 py-3 transition-colors duration-200 last:border-b-0 hover:bg-accent/50 sm:gap-3 sm:px-4">
                <div className="flex min-w-0 flex-1 gap-2">
                    <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2">
                            <span className="truncate text-sm font-medium">
                                {fullName}
                            </span>
                            {user.email_confirmed ? (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <CheckCircle2
                                            className="size-4 shrink-0 text-green-500"
                                            aria-label={t(
                                                'user.row.email_confirmed',
                                            )}
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {t('user.row.email_confirmed')}
                                    </TooltipContent>
                                </Tooltip>
                            ) : (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <AlertTriangle
                                            className="size-4 shrink-0 text-amber-500"
                                            aria-label={t(
                                                'user.row.email_not_confirmed',
                                            )}
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {t('user.row.email_not_confirmed')}
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </div>
                        <span className="truncate text-xs text-muted-foreground">
                            {user.email}
                        </span>
                    </div>
                    <div>
                        {user.roles && user.roles.length > 0 && (
                            <div className="flex flex-wrap gap-1 pt-0.5">
                                {user.roles.map((role) => (
                                    <RoleBadge key={role} role={role} />
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    {canManage && (
                        <>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={
                                            user.email_confirmed || resending
                                        }
                                        onClick={handleResend}
                                        aria-label={t('user.row.resend_label')}
                                        className="cursor-pointer"
                                    >
                                        <Mail className="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {user.email_confirmed
                                        ? t('user.row.email_already_confirmed')
                                        : t('user.row.resend_invitation')}
                                </TooltipContent>
                            </Tooltip>
                            {onEdit && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => onEdit(user)}
                                            aria-label={t(
                                                'user.row.edit_label',
                                                { name: fullName },
                                            )}
                                        >
                                            <Pencil className="size-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {t('user.row.edit_tooltip')}
                                    </TooltipContent>
                                </Tooltip>
                            )}
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            setShowDeleteConfirm(true)
                                        }
                                        aria-label={t('user.row.delete_label', {
                                            name: fullName,
                                        })}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('user.row.delete_tooltip')}
                                </TooltipContent>
                            </Tooltip>
                        </>
                    )}
                </div>
            </div>

            <ConfirmationDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
                title={t('user.delete_dialog.title')}
                description={t('user.delete_dialog.description', {
                    name: fullName,
                })}
                confirmLabel={t('user.delete_dialog.confirm')}
                variant="destructive"
                onConfirm={handleDelete}
            />
        </>
    );
}
