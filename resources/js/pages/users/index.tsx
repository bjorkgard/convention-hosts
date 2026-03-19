import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Users } from 'lucide-react';
import { useState } from 'react';

import { index as conventionsIndex, show } from '@/actions/App/Http/Controllers/ConventionController';
import { index as usersIndex, store, update } from '@/actions/App/Http/Controllers/UserController';
import UserRow from '@/components/conventions/user-row';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { ConventionUser, Role } from '@/types/user';

const ALL_ROLES: Role[] = ['Owner', 'Administrator'];

interface UserFormData {
    first_name: string;
    last_name: string;
    email: string;
    mobile: string;
    roles: Role[];
}

const emptyForm: UserFormData = {
    first_name: '',
    last_name: '',
    email: '',
    mobile: '',
    roles: [],
};

interface UsersIndexProps {
    convention: Convention;
    users: ConventionUser[];
    floors: Floor[];
    userRoles: Role[];
}

export default function UsersIndex({ convention, users }: UsersIndexProps) {
    const { isManager } = useConventionRole();

    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingUser, setEditingUser] = useState<ConventionUser | null>(null);

    const addForm = useForm<UserFormData>({ ...emptyForm });
    const editForm = useForm<UserFormData>({ ...emptyForm });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conventions', href: conventionsIndex.url() },
        { title: convention.name, href: show.url(convention.id) },
        { title: 'Users', href: usersIndex.url(convention.id) },
    ];

    function toggleRole(form: ReturnType<typeof useForm<UserFormData>>, role: Role) {
        const current = form.data.roles;
        if (current.includes(role)) {
            form.setData('roles', current.filter((r) => r !== role));
        } else {
            form.setData('roles', [...current, role]);
        }
    }

    function handleAdd(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(store.url(convention.id), {
            onSuccess: () => {
                addForm.reset();
                setShowAddDialog(false);
            },
        });
    }

    function handleEdit(e: React.FormEvent) {
        if (!editingUser) return;
        e.preventDefault();
        editForm.put(update.url({ convention: convention.id, user: editingUser.id }), {
            onSuccess: () => {
                editForm.reset();
                setEditingUser(null);
            },
        });
    }

    function openEditDialog(user: ConventionUser) {
        editForm.setData({
            first_name: user.first_name,
            last_name: user.last_name,
            email: user.email,
            mobile: user.mobile ?? '',
            roles: user.roles ?? [],
        });
        setEditingUser(user);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Users — ${convention.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" asChild className="shrink-0">
                            <Link href={show.url(convention.id)}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Users</h1>
                            <p className="text-muted-foreground text-sm">
                                Manage who has access to this convention. Invite users and assign roles to control what they can see and do.
                            </p>
                        </div>
                    </div>

                    {isManager && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    className="cursor-pointer gap-1.5"
                                    onClick={() => setShowAddDialog(true)}
                                >
                                    <Plus className="size-4" />
                                    <span className="hidden sm:inline">Add User</span>
                                    <span className="sm:hidden">Add</span>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Invite a new user by email and assign their role</TooltipContent>
                        </Tooltip>
                    )}
                </div>

                {/* Users list */}
                {users.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <Users className="text-muted-foreground mb-2 size-8" />
                        <p className="text-muted-foreground">No users yet.</p>
                        {isManager && (
                            <Button
                                variant="link"
                                className="mt-2 cursor-pointer"
                                onClick={() => setShowAddDialog(true)}
                            >
                                Invite your first user
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="rounded-xl border border-border">
                        {users.map((user) => (
                            <UserRow
                                key={user.id}
                                user={user}
                                convention={convention}
                                canManage={isManager}
                                onEdit={isManager ? openEditDialog : undefined}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Add user dialog */}
            <UserFormDialog
                open={showAddDialog}
                onOpenChange={setShowAddDialog}
                title="Add User"
                description={`Invite a new user to ${convention.name}.`}
                form={addForm}
                onSubmit={handleAdd}
                submitLabel="Invite User"
                submittingLabel="Inviting..."
                onToggleRole={(role) => toggleRole(addForm, role)}
            />

            {/* Edit user dialog */}
            <UserFormDialog
                open={!!editingUser}
                onOpenChange={(open) => !open && setEditingUser(null)}
                title="Edit User"
                description="Update user details and roles."
                form={editForm}
                onSubmit={handleEdit}
                submitLabel="Save"
                submittingLabel="Saving..."
                onToggleRole={(role) => toggleRole(editForm, role)}
            />
        </AppLayout>
    );
}

interface UserFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    form: ReturnType<typeof useForm<UserFormData>>;
    onSubmit: (e: React.FormEvent) => void;
    submitLabel: string;
    submittingLabel: string;
    onToggleRole: (role: Role) => void;
}

function UserFormDialog({
    open,
    onOpenChange,
    title,
    description,
    form,
    onSubmit,
    submitLabel,
    submittingLabel,
    onToggleRole,
}: UserFormDialogProps) {

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-md">
                <form onSubmit={onSubmit}>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        {/* First name */}
                        <div className="grid gap-2">
                            <Label htmlFor="user-first-name">First Name</Label>
                            <Input
                                id="user-first-name"
                                value={form.data.first_name}
                                onChange={(e) => form.setData('first_name', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.first_name} />
                        </div>

                        {/* Last name */}
                        <div className="grid gap-2">
                            <Label htmlFor="user-last-name">Last Name</Label>
                            <Input
                                id="user-last-name"
                                value={form.data.last_name}
                                onChange={(e) => form.setData('last_name', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.last_name} />
                        </div>

                        {/* Email */}
                        <div className="grid gap-2">
                            <Label htmlFor="user-email">Email</Label>
                            <Input
                                id="user-email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        {/* Mobile */}
                        <div className="grid gap-2">
                            <Label htmlFor="user-mobile">Mobile</Label>
                            <Input
                                id="user-mobile"
                                type="tel"
                                value={form.data.mobile}
                                onChange={(e) => form.setData('mobile', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.mobile} />
                        </div>

                        {/* Roles */}
                        <div className="grid gap-2">
                            <Label>Roles</Label>
                            <div className="grid grid-cols-2 gap-2">
                                {ALL_ROLES.map((role) => (
                                    <label
                                        key={role}
                                        className="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={form.data.roles.includes(role)}
                                            onCheckedChange={() => onToggleRole(role)}
                                        />
                                        {role}
                                    </label>
                                ))}
                            </div>
                            <InputError message={form.errors.roles} />
                        </div>
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline" className="cursor-pointer">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing} className="cursor-pointer">
                            {form.processing ? submittingLabel : submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
