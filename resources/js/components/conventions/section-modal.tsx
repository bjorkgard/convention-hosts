import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/SectionController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Convention, Floor, Section } from '@/types/convention';

interface SectionModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    convention: Convention;
    floors: Floor[];
    section?: Section | null;
}

export default function SectionModal({
    open,
    onOpenChange,
    convention,
    floors,
    section,
}: SectionModalProps) {
    const { t } = useTranslation();
    const isEdit = !!section;

    const form = useForm({
        floor_id: '' as string | '',
        name: '',
        number_of_seats: '' as number | '',
        elder_friendly: false,
        handicap_friendly: false,
        hearing_loop: false,
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
                hearing_loop: section.hearing_loop,
                information: section.information ?? '',
            });
        } else {
            form.reset();
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

            form.post(
                store.url({ convention: convention.id, floor: floorId }),
                {
                    onSuccess: () => {
                        form.reset();
                        onOpenChange(false);
                    },
                },
            );
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEdit
                                ? t('section.modal.edit_title')
                                : t('section.modal.add_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {isEdit
                                ? t('section.modal.edit_description')
                                : t('section.modal.add_description', {
                                      convention: convention.name,
                                  })}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {isEdit ? (
                            <div className="grid gap-2">
                                <Label>{t('section.modal.floor_label')}</Label>
                                <Input
                                    value={
                                        floors.find(
                                            (f) => f.id === section?.floor_id,
                                        )?.name ?? ''
                                    }
                                    disabled
                                />
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor="section-floor">
                                    {t('section.modal.floor_label')}
                                </Label>
                                <Select
                                    value={
                                        form.data.floor_id
                                            ? String(form.data.floor_id)
                                            : ''
                                    }
                                    onValueChange={(value) =>
                                        form.setData('floor_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="section-floor"
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'section.modal.floor_placeholder',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {floors.map((floor) => (
                                            <SelectItem
                                                key={floor.id}
                                                value={String(floor.id)}
                                            >
                                                {floor.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.floor_id} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="section-name">
                                {t('section.modal.name_label')}
                            </Label>
                            <Input
                                id="section-name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder={t(
                                    'section.modal.name_placeholder',
                                )}
                                autoFocus
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="section-seats">
                                {t('section.modal.seats_label')}
                            </Label>
                            <Input
                                id="section-seats"
                                type="number"
                                min={1}
                                value={form.data.number_of_seats}
                                onChange={(e) =>
                                    form.setData(
                                        'number_of_seats',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                placeholder={t(
                                    'section.modal.seats_placeholder',
                                )}
                                required
                            />
                            <InputError message={form.errors.number_of_seats} />
                        </div>

                        <div className="flex flex-wrap gap-6">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="section-elder"
                                    checked={form.data.elder_friendly}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'elder_friendly',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="section-elder"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    {t('section.modal.elder_friendly_label')}
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="section-handicap"
                                    checked={form.data.handicap_friendly}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'handicap_friendly',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="section-handicap"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    {t('section.modal.handicap_friendly_label')}
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="section-hearing-loop"
                                    checked={form.data.hearing_loop}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'hearing_loop',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="section-hearing-loop"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    {t('section.modal.hearing_loop_label')}
                                </Label>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="section-info">
                                {t('section.modal.info_label')}
                            </Label>
                            <textarea
                                id="section-info"
                                className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                value={form.data.information}
                                onChange={(e) =>
                                    form.setData('information', e.target.value)
                                }
                                placeholder={t(
                                    'section.modal.info_placeholder',
                                )}
                                rows={3}
                            />
                            <InputError message={form.errors.information} />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="outline"
                                className="cursor-pointer"
                            >
                                {t('section.modal.cancel')}
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="cursor-pointer"
                        >
                            {form.processing
                                ? isEdit
                                    ? t('section.modal.saving')
                                    : t('section.modal.adding')
                                : isEdit
                                  ? t('section.modal.edit_submit')
                                  : t('section.modal.add_submit')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
