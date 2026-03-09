import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import { store, update } from '@/actions/App/Http/Controllers/SectionController';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Convention, Floor, Section } from '@/types/convention';

interface SectionModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    convention: Convention;
    floors: Floor[];
    section?: Section | null;
}

export default function SectionModal({ open, onOpenChange, convention, floors, section }: SectionModalProps) {
    const isEdit = !!section;

    const form = useForm({
        floor_id: '' as string | '',
        name: '',
        number_of_seats: '' as number | '',
        elder_friendly: false,
        handicap_friendly: false,
        information: '',
    });

    useEffect(() => {
        if (!open) return;

        if (isEdit && section) {
            form.setData({
                floor_id: section.floor_id,
                name: section.name,
                number_of_seats: section.number_of_seats,
                elder_friendly: section.elder_friendly,
                handicap_friendly: section.handicap_friendly,
                information: section.information ?? '',
            });
        } else {
            form.reset();
            // Auto-select floor when only one is available
            if (floors.length === 1) {
                form.setData('floor_id', floors[0].id);
            }
        }
        form.clearErrors();
    }, [open, section]); // eslint-disable-line react-hooks/exhaustive-deps

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEdit && section) {
            form.put(update.url(section.id), {
                onSuccess: () => onOpenChange(false),
            });
        } else {
            const floorId = form.data.floor_id;
            if (!floorId) return;

            form.post(store.url({ convention: convention.id, floor: floorId }), {
                onSuccess: () => {
                    form.reset();
                    onOpenChange(false);
                },
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>{isEdit ? 'Edit Section' : 'Add Section'}</DialogTitle>
                        <DialogDescription>
                            {isEdit
                                ? 'Update the section details.'
                                : `Add a new section to ${convention.name}.`}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Floor selector — shown in create mode, read-only display in edit mode */}
                        {isEdit ? (
                            <div className="grid gap-2">
                                <Label>Floor</Label>
                                <Input
                                    value={floors.find((f) => f.id === section?.floor_id)?.name ?? ''}
                                    disabled
                                />
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="section-floor">Floor</Label>
                                <Select
                                    value={form.data.floor_id ? String(form.data.floor_id) : ''}
                                    onValueChange={(value) => form.setData('floor_id', value)}
                                >
                                    <SelectTrigger id="section-floor" className="w-full">
                                        <SelectValue placeholder="Select a floor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {floors.map((floor) => (
                                            <SelectItem key={floor.id} value={String(floor.id)}>
                                                {floor.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.floor_id} />
                            </div>
                        )}

                        {/* Name */}
                        <div className="grid gap-2">
                            <Label htmlFor="section-name">Name</Label>
                            <Input
                                id="section-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="e.g. Section A"
                                autoFocus
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        {/* Number of seats */}
                        <div className="grid gap-2">
                            <Label htmlFor="section-seats">Number of Seats</Label>
                            <Input
                                id="section-seats"
                                type="number"
                                min={1}
                                value={form.data.number_of_seats}
                                onChange={(e) =>
                                    form.setData('number_of_seats', e.target.value === '' ? '' : Number(e.target.value))
                                }
                                placeholder="e.g. 100"
                                required
                            />
                            <InputError message={form.errors.number_of_seats} />
                        </div>

                        {/* Accessibility checkboxes */}
                        <div className="flex flex-wrap gap-6">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="section-elder"
                                    checked={form.data.elder_friendly}
                                    onCheckedChange={(checked) => form.setData('elder_friendly', checked === true)}
                                />
                                <Label htmlFor="section-elder" className="cursor-pointer text-sm font-normal">
                                    Elder friendly
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="section-handicap"
                                    checked={form.data.handicap_friendly}
                                    onCheckedChange={(checked) => form.setData('handicap_friendly', checked === true)}
                                />
                                <Label htmlFor="section-handicap" className="cursor-pointer text-sm font-normal">
                                    Handicap friendly
                                </Label>
                            </div>
                        </div>

                        {/* Information */}
                        <div className="grid gap-2">
                            <Label htmlFor="section-info">Information (optional)</Label>
                            <textarea
                                id="section-info"
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                value={form.data.information}
                                onChange={(e) => form.setData('information', e.target.value)}
                                placeholder="Additional notes about this section"
                                rows={3}
                            />
                            <InputError message={form.errors.information} />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline" className="cursor-pointer">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing} className="cursor-pointer">
                            {form.processing
                                ? isEdit
                                    ? 'Saving...'
                                    : 'Adding...'
                                : isEdit
                                  ? 'Save'
                                  : 'Add Section'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
