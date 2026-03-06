import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useState } from 'react';

import { index as conventionsIndex, show } from '@/actions/App/Http/Controllers/ConventionController';
import { destroy, index as floorsIndex, store, update } from '@/actions/App/Http/Controllers/FloorController';
import FloorRow from '@/components/conventions/floor-row';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { Role } from '@/types/user';

interface FloorsIndexProps {
    convention: Convention;
    floors: Floor[];
    userRoles: Role[];
    userFloorIds: number[];
    userSectionIds: number[];
}

export default function FloorsIndex({ convention, floors }: FloorsIndexProps) {
    const { isOwner, isConventionUser } = useConventionRole();
    const isManager = isOwner || isConventionUser;
    const userRole: Role = isOwner ? 'Owner' : isConventionUser ? 'ConventionUser' : 'FloorUser';

    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingFloor, setEditingFloor] = useState<Floor | null>(null);
    const [deletingFloor, setDeletingFloor] = useState<Floor | null>(null);

    const addForm = useForm({ name: '' });
    const editForm = useForm({ name: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conventions', href: conventionsIndex.url() },
        { title: convention.name, href: show.url(convention.id) },
        { title: 'Floors', href: floorsIndex.url(convention.id) },
    ];

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
        if (!editingFloor) return;
        e.preventDefault();
        editForm.put(update.url(editingFloor.id), {
            onSuccess: () => {
                editForm.reset();
                setEditingFloor(null);
            },
        });
    }

    function openEditDialog(floor: Floor) {
        editForm.setData('name', floor.name);
        setEditingFloor(floor);
    }

    function handleDelete() {
        if (!deletingFloor) return;
        router.delete(destroy.url(deletingFloor.id), {
            onSuccess: () => setDeletingFloor(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Floors — ${convention.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" asChild className="shrink-0">
                            <Link href={show.url(convention.id)}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-semibold tracking-tight">Floors</h1>
                    </div>

                    {isManager && (
                        <Button
                            className="cursor-pointer gap-1.5"
                            onClick={() => setShowAddDialog(true)}
                        >
                            <Plus className="size-4" />
                            <span className="hidden sm:inline">Add Floor</span>
                            <span className="sm:hidden">Add</span>
                        </Button>
                    )}
                </div>

                {/* Floors list */}
                {floors.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-muted-foreground">No floors yet.</p>
                        {isManager && (
                            <Button
                                variant="link"
                                className="mt-2 cursor-pointer"
                                onClick={() => setShowAddDialog(true)}
                            >
                                Add your first floor
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col gap-2">
                        {floors.map((floor) => (
                            <FloorRow
                                key={floor.id}
                                floor={floor}
                                sections={floor.sections ?? []}
                                userRole={userRole}
                                onEdit={openEditDialog}
                                onDelete={(f) => setDeletingFloor(f)}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Add floor dialog */}
            <Dialog open={showAddDialog} onOpenChange={setShowAddDialog}>
                <DialogContent>
                    <form onSubmit={handleAdd}>
                        <DialogHeader>
                            <DialogTitle>Add Floor</DialogTitle>
                            <DialogDescription>Add a new floor to {convention.name}.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2 py-4">
                            <Label htmlFor="add-floor-name">Floor Name</Label>
                            <Input
                                id="add-floor-name"
                                value={addForm.data.name}
                                onChange={(e) => addForm.setData('name', e.target.value)}
                                placeholder="e.g. Ground Floor"
                                autoFocus
                                required
                            />
                            <InputError message={addForm.errors.name} />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline" className="cursor-pointer">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={addForm.processing} className="cursor-pointer">
                                {addForm.processing ? 'Adding...' : 'Add Floor'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit floor dialog */}
            <Dialog open={!!editingFloor} onOpenChange={(open) => !open && setEditingFloor(null)}>
                <DialogContent>
                    <form onSubmit={handleEdit}>
                        <DialogHeader>
                            <DialogTitle>Edit Floor</DialogTitle>
                            <DialogDescription>Update the floor name.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2 py-4">
                            <Label htmlFor="edit-floor-name">Floor Name</Label>
                            <Input
                                id="edit-floor-name"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                autoFocus
                                required
                            />
                            <InputError message={editForm.errors.name} />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline" className="cursor-pointer">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={editForm.processing} className="cursor-pointer">
                                {editForm.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete confirmation dialog */}
            <Dialog open={!!deletingFloor} onOpenChange={(open) => !open && setDeletingFloor(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Floor</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{deletingFloor?.name}"? All sections on this floor will also be deleted. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline" className="cursor-pointer">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            className="cursor-pointer"
                            onClick={handleDelete}
                        >
                            Delete floor
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
