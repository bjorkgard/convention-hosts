import { Head, Link, router, useForm } from '@inertiajs/react';
import { Accessibility, ArrowLeft, Ear, Heart, Plus } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { index as conventionsIndex, show as conventionShow } from '@/actions/App/Http/Controllers/ConventionController';
import { index as floorsIndex } from '@/actions/App/Http/Controllers/FloorController';
import { destroy, store } from '@/actions/App/Http/Controllers/SectionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import SectionCard from '@/components/conventions/section-card';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog, DialogClose, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor, Section } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { Role } from '@/types/user';

interface SectionsIndexProps {
    convention: Convention;
    floor: Floor;
    sections: Section[];
    userRoles: Role[];
}

export default function SectionsIndex({ convention, floor, sections }: SectionsIndexProps) {
    const { t } = useTranslation();
    const { isManager } = useConventionRole();
    const canAddSection = isManager;
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [deletingSection, setDeletingSection] = useState<Section | null>(null);
    const addForm = useForm({
        name: '', number_of_seats: '', elder_friendly: false,
        handicap_friendly: false, hearing_loop: false, information: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: conventionsIndex.url() },
        { title: convention.name, href: conventionShow.url(convention.id) },
        { title: t('floor.index.heading'), href: floorsIndex.url(convention.id) },
        { title: floor.name, href: `${floorsIndex.url(convention.id)}?open=${floor.id}` },
    ];

    function handleAdd(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(store.url({ convention: convention.id, floor: floor.id }), {
            onSuccess: () => { addForm.reset(); setShowAddDialog(false); },
        });
    }

    function handleDelete() {
        if (!deletingSection) return;
        router.delete(destroy.url(deletingSection.id), { onSuccess: () => setDeletingSection(null) });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('section.index.title', { floor: floor.name })} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" asChild className="shrink-0">
                            <Link href={floorsIndex.url(convention.id)}><ArrowLeft /></Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">{t('section.index.heading')}</h1>
                            <p className="text-muted-foreground text-sm">{t('section.index.description', { floor: floor.name })}</p>
                        </div>
                    </div>
                    {canAddSection && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button className="cursor-pointer gap-1.5" onClick={() => setShowAddDialog(true)}>
                                    <Plus className="size-4" />
                                    <span className="hidden sm:inline">{t('section.index.add_button')}</span>
                                    <span className="sm:hidden">{t('section.index.add_short')}</span>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('section.index.add_tooltip')}</TooltipContent>
                        </Tooltip>
                    )}
                </div>

                {sections.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <p className="text-muted-foreground">{t('section.index.empty')}</p>
                        {canAddSection && (
                            <Button variant="link" className="mt-2 cursor-pointer" onClick={() => setShowAddDialog(true)}>
                                {t('section.index.empty_add')}
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col gap-2">
                        {sections.map((section) => (
                            <SectionCard key={section.id} section={section} />
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={showAddDialog} onOpenChange={setShowAddDialog}>
                <DialogContent>
                    <form onSubmit={handleAdd}>
                        <DialogHeader>
                            <DialogTitle>{t('section.modal.add_title')}</DialogTitle>
                            <DialogDescription>{t('section.modal.add_description', { convention: floor.name })}</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-3 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="add-section-name">{t('section.modal.name_label')}</Label>
                                <Input id="add-section-name" value={addForm.data.name} onChange={(e) => addForm.setData('name', e.target.value)} placeholder={t('section.modal.name_placeholder')} autoFocus required />
                                <InputError message={addForm.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="add-section-seats">{t('section.modal.seats_label')}</Label>
                                <Input id="add-section-seats" type="number" min={1} value={addForm.data.number_of_seats} onChange={(e) => addForm.setData('number_of_seats', e.target.value)} placeholder={t('section.modal.seats_placeholder')} required />
                                <InputError message={addForm.errors.number_of_seats} />
                            </div>
                            <div className="flex items-center gap-4">
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={addForm.data.elder_friendly} onCheckedChange={(checked) => addForm.setData('elder_friendly', !!checked)} />
                                    <Heart className="text-muted-foreground size-4" />
                                    {t('section.modal.elder_friendly_label')}
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={addForm.data.handicap_friendly} onCheckedChange={(checked) => addForm.setData('handicap_friendly', !!checked)} />
                                    <Accessibility className="text-muted-foreground size-4" />
                                    {t('section.modal.handicap_friendly_label')}
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={addForm.data.hearing_loop} onCheckedChange={(checked) => addForm.setData('hearing_loop', !!checked)} />
                                    <Ear className="text-muted-foreground size-4" />
                                    {t('section.modal.hearing_loop_label')}
                                </label>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="add-section-info">{t('section.modal.info_label')}</Label>
                                <Input id="add-section-info" value={addForm.data.information} onChange={(e) => addForm.setData('information', e.target.value)} placeholder={t('section.modal.info_placeholder')} />
                                <InputError message={addForm.errors.information} />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline" className="cursor-pointer">{t('section.modal.cancel')}</Button>
                            </DialogClose>
                            <Button type="submit" disabled={addForm.processing} className="cursor-pointer">
                                {addForm.processing ? t('section.modal.adding') : t('section.modal.add_submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={!!deletingSection}
                onOpenChange={(open) => !open && setDeletingSection(null)}
                title={t('section.delete_dialog.title')}
                description={t('section.delete_dialog.description', { name: deletingSection?.name ?? '' })}
                confirmLabel={t('section.delete_dialog.confirm')}
                variant="destructive"
                onConfirm={handleDelete}
            />
        </AppLayout>
    );
}
