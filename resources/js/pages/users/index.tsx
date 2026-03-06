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
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { ConventionUser, Role } from '@/types/user';

const ALL_ROLES: Role[] = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];

interface UserFormData {
    first_name: string;
    last_name: string;
    email: string;
    mobile: string;
    roles: Role[];
    floor_ids: number[];
    section_ids: number[];
}

const emptyForm: UserFormData = {
    first_name: '',
    last_name: '',
    email: '',
    mobile: '',
    roles: [],
    floor_ids: [],
    section_ids: [],
};

interface UsersIndexProps {
    convention: Convention;
    users: ConventionUser[];
    floors: Floor[];
    userRoles: Role[];
}

export default function UsersIndex({ convention, users, floors }: UsersIndexProps) {
    const { isOwner, isConventionUser } = useConventionRole();
    const isManager = isOwner || isConventionUser;

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
            if (role === 'FloorUser') form.setData('floor_ids', []);
            if (role === 'SectionUser') form.setData('section_ids', []);
        } else {
            form.setData('roles', [...current, role]);
        }
    }

    function toggleFloorId(form: ReturnType<typeof useForm<UserFormData>>, floorId: number) {
        const current = form.data.floor_ids;
        if (current.includes(floorId)) {
            form.setData('floor_ids', current.filter((id) => id !== floorId));
        } else {
            form.setData('floor_ids', [...current, floorId]);
        }
    }

    function toggleSectionId(form: ReturnType<typeof useForm<UserFormData>>, sectionId: number) {
        const current = form.data.section_ids;
        if (current.includes(sectionId)) {
            form.setData('section_ids', current.filter((id) => id !== sectionId));
        } else {
            form.setData('section_ids', [...current, sectionId]);
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
            floor_ids: user.floor_ids ?? [],
            section_ids: user.section_ids ?? [],
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
                        <h1 className="text-2xl font-semibold tracking-tight">Users</h1>
                    </div>

                    {isManager && (
                        <Button
                            className="cursor-pointer gap-1.5"
                            onClick={() => setShowAddDialog(true)}
                        >
                            <Plus className="size-4" />
                            <span className="hidden sm:inline">Add User</span>
                            <span className="sm:hidden">Add</span>
                        </Button>
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
                floors={floors}
                onSubmit={handleAdd}
                submitLabel="Invite User"
                submittingLabel="Inviting..."
                onToggleRole={(role) => toggleRole(addForm, role)}
                onToggleFloorId={(id) => toggleFloorId(addForm, id)}
                onToggleSectionId={(id) => toggleSectionId(addForm, id)}
            />

            {/* Edit user dialog */}
            <UserFormDialog
                open={!!editingUser}
                onOpenChange={(open) => !open && setEditingUser(null)}
                title="Edit User"
                description="Update user details and roles."
                form={editForm}
                floors={floors}
                onSubmit={handleEdit}
                submitLabel="Save"
                submittingLabel="Saving..."
                onToggleRole={(role) => toggleRole(editForm, role)}
                onToggleFloorId={(id) => toggleFloorId(editForm, id)}
                onToggleSectionId={(id) => toggleSectionId(editForm, id)}
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
    floors: Floor[];
    onSubmit: (e: React.FormEvent) => void;
    submitLabel: string;
    submittingLabel: string;
    onToggleRole: (role: Role) => void;
    onToggleFloorId: (id: number) => void;
    onToggleSectionId: (id: number) => void;
}

function UserFormDialog({
    open,
    onOpenChange,
    title,
    description,
    form,
    floors,
    onSubmit,
    submitLabel,
    submittingLabel,
    onToggleRole,
    onToggleFloorId,
    onToggleSectionId,
}: UserFormDialogProps) {
    const showFloorSelect = form.data.roles.includes('FloorUser');
    const showSectionSelect = form.data.roles.includes('SectionUser');

    // Flatten all sections from all floors for section selection
    const allSections = floors.flatMap(
        (floor) => (floor.sections ?? []).map((section) => ({ ...section, floorName: floor.name })),
    );

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

                        {/* Floor selection (when FloorUser role is selected) */}
                        {showFloorSelect && (
                            <div className="grid gap-2">
                                <Label>Assign Floors</Label>
                                <div className="max-h-32 space-y-1 overflow-y-auto rounded-md border p-2">
                                    {floors.map((floor) => (
                                        <label
                                            key={floor.id}
                                            className="flex cursor-pointer items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.floor_ids.includes(floor.id)}
                                                onCheckedChange={() => onToggleFloorId(floor.id)}
                                            />
                                            {floor.name}
                                        </label>
                                    ))}
                                    {floors.length === 0 && (
                                        <p className="text-muted-foreground text-xs">
                                            No floors available.
                                        </p>
                                    )}
                                </div>
                                <InputError message={form.errors.floor_ids} />
                            </div>
                        )}

                        {/* Section selection (when SectionUser role is selected) */}
                        {showSectionSelect && (
                            <div className="grid gap-2">
                                <Label>Assign Sections</Label>
                                <div className="max-h-40 space-y-1 overflow-y-auto rounded-md border p-2">
                                    {allSections.map((section) => (
                                        <label
                                            key={section.id}
                                            className="flex cursor-pointer items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.section_ids.includes(section.id)}
                                                onCheckedChange={() => onToggleSectionId(section.id)}
                                            />
                                            <span>
                                                {section.name}
                                                <span className="text-muted-foreground ml-1 text-xs">
                                                    ({section.floorName})
                                                </span>
                                            </span>
                                        </label>
                                    ))}
                                    {allSections.length === 0 && (
                                        <p className="text-muted-foreground text-xs">
                                            No sections available.
                                        </p>
                                    )}
                                </div>
                                <InputError message={form.errors.section_ids} />
                            </div>
                        )}
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
