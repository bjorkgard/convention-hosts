import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { index as conventionsIndex, show } from '@/actions/App/Http/Controllers/ConventionController';
import { destroy, index as floorsIndex, store, update } from '@/actions/App/Http/Controllers/FloorController';
import { destroy as destroySection } from '@/actions/App/Http/Controllers/SectionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import FloorRow from '@/components/conventions/floor-row';
import SectionModal from '@/components/conventions/section-modal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor, Section } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { Role } from '@/types/user';

interface FloorsIndexProps {
    convention: Convention;
    floors: Floor[];
    userRoles: Role[];
}

export default function FloorsIndex({ convention, floors }: FloorsIndexProps) {
    const { t } = useTranslation();
    const { isOwner, isAdministrator, isManager } = useConventionRole();
    const canAddSection = isManager;
    const userRole: Role | null = isOwner ? 'Owner' : isAdministrator ? 'Administrator' : null;

    const openFloorId = new URLSearchParams(usePage().url.split('?')[1] ?? '').get('open');

    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingFloor, setEditingFloor] = useState<Floor | null>(null);
    const [deletingFloor, setDeletingFloor] = useState<Floor | null>(null);
    const [showSectionModal, setShowSectionModal] = useState(false);
    const [editingSection, setEditingSection] = useState<Section | null>(null);
    const [deletingSection, setDeletingSection] = useState<Section | null>(null);

    const sectionModalFloors = useMemo(() => floors, [floors]);

    const addForm = useForm({ name: '' });
    const editForm = useForm({ name: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: conventionsIndex.url() },
        { title: convention.name, href: show.url(convention.id) },
        { title: t('floor.index.heading'), href: floorsIndex.url(convention.id) },
    ];

    function handleAdd(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(store.url(convention.id), {
            onSuccess: () => { addForm.reset(); setShowAddDialog(false); },
        });
    }

    function handleEdit(e: React.FormEvent) {
        if (!editingFloor) return;
        e.preventDefault();
        editForm.put(update.url(editingFloor.id), {
            onSuccess: () => { editForm.reset(); setEditingFloor(null); },
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

    function openSectionCreate() { setEditingSection(null); setShowSectionModal(true); }
    function openSectionEdit(section: Section) { setEditingSection(section); setShowSectionModal(true); }

    function handleDeleteSection() {
        if (!deletingSection) return;
        router.delete(destroySection.url(deletingSection.id), {
            onSuccess: () => setDeletingSection(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('floor.index.title', { convention: convention.name })} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" asChild className="shrink-0">
                            <Link href={show.url(convention.id)}><ArrowLeft /></Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">{t('floor.index.heading')}</h1>
                            <p className="text-muted-foreground text-sm">{t('floor.index.description')}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {canAddSection && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button variant="outline" className="cursor-pointer gap-1.5" onClick={openSectionCreate}>
                                        <Plus className="size-4" />
                                        <span className="hidden sm:inline">{t('floor.index.add_section_button')}</span>
                                        <span className="sm:hidden">{t('floor.index.add_section_short')}</span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{t('floor.index.add_section_tooltip')}</TooltipContent>
                            </Tooltip>
                        )}
                        {isManager && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button className="cursor-pointer gap-1.5" onClick={() => setShowAddDialog(true)}>
                                        <Plus className="size-4" />
                                        <span className="hidden sm:inline">{t('floor.index.add_floor_button')}</span>
                                        <span className="sm:hidden">{t('floor.index.add_floor_short')}</span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{t('floor.index.add_floor_tooltip')}</TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                </div>

                {floors.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <p className="text-muted-foreground">{t('floor.index.empty')}</p>
                        {isManager && (
                            <Button variant="link" className="mt-2 cursor-pointer" onClick={() => setShowAddDialog(true)}>
                                {t('floor.index.empty_add')}
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
                                defaultOpen={openFloorId === String(floor.id)}
                                onEdit={openEditDialog}
                                onDelete={(f) => setDeletingFloor(f)}
                                onEditSection={openSectionEdit}
                                onDeleteSection={(s) => setDeletingSection(s)}
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
                            <DialogTitle>{t('floor.add_dialog.title')}</DialogTitle>
                            <DialogDescription>{t('floor.add_dialog.description', { convention: convention.name })}</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2 py-4">
                            <Label htmlFor="add-floor-name">{t('floor.add_dialog.name_label')}</Label>
                            <Input
                                id="add-floor-name"
                                value={addForm.data.name}
                                onChange={(e) => addForm.setData('name', e.target.value)}
                                placeholder={t('floor.add_dialog.name_placeholder')}
                                autoFocus
                                required
                            />
                            <InputError message={addForm.errors.name} />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline" className="cursor-pointer">{t('floor.add_dialog.cancel')}</Button>
                            </DialogClose>
                            <Button type="submit" disabled={addForm.processing} className="cursor-pointer">
                                {addForm.processing ? t('floor.add_dialog.submitting') : t('floor.add_dialog.submit')}
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
                            <DialogTitle>{t('floor.edit_dialog.title')}</DialogTitle>
                            <DialogDescription>{t('floor.edit_dialog.description')}</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2 py-4">
                            <Label htmlFor="edit-floor-name">{t('floor.edit_dialog.name_label')}</Label>
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
                                <Button type="button" variant="outline" className="cursor-pointer">{t('floor.edit_dialog.cancel')}</Button>
                            </DialogClose>
                            <Button type="submit" disabled={editForm.processing} className="cursor-pointer">
                                {editForm.processing ? t('floor.edit_dialog.submitting') : t('floor.edit_dialog.submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={!!deletingFloor}
                onOpenChange={(open) => !open && setDeletingFloor(null)}
                title={t('floor.delete_dialog.title')}
                description={t('floor.delete_dialog.description', { name: deletingFloor?.name ?? '' })}
                confirmLabel={t('floor.delete_dialog.confirm')}
                variant="destructive"
                onConfirm={handleDelete}
            />

            <SectionModal
                open={showSectionModal}
                onOpenChange={setShowSectionModal}
                convention={convention}
                floors={sectionModalFloors}
                section={editingSection}
            />

            <ConfirmationDialog
                open={!!deletingSection}
                onOpenChange={(open) => !open && setDeletingSection(null)}
                title={t('section.delete_dialog.title')}
                description={t('section.delete_dialog.description', { name: deletingSection?.name ?? '' })}
                confirmLabel={t('section.delete_dialog.confirm')}
                variant="destructive"
                onConfirm={handleDeleteSection}
            />
        </AppLayout>
    );
}
