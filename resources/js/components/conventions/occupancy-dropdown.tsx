import { useTranslation } from 'react-i18next';

import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getOccupancyColorClass } from '@/hooks/use-occupancy-color';
import { cn } from '@/lib/utils';

const OCCUPANCY_OPTIONS = [0, 10, 25, 50, 75, 100] as const;

interface OccupancyDropdownProps {
    currentOccupancy: number;
    onUpdate: (occupancy: number) => void;
}

export default function OccupancyDropdown({
    currentOccupancy,
    onUpdate,
}: OccupancyDropdownProps) {
    const { t } = useTranslation();

    function handleChange(value: string) {
        const occupancy = Number(value);
        if (occupancy !== currentOccupancy) {
            onUpdate(occupancy);
        }
    }

    return (
        <div className="space-y-2">
            <Label htmlFor="occupancy-select">
                {t('section.occupancy.label')}
            </Label>
            <Select
                value={String(currentOccupancy)}
                onValueChange={handleChange}
            >
                <SelectTrigger id="occupancy-select" className="w-full">
                    <SelectValue
                        placeholder={t('section.occupancy.placeholder')}
                    />
                </SelectTrigger>
                <SelectContent>
                    {OCCUPANCY_OPTIONS.map((option) => (
                        <SelectItem key={option} value={String(option)}>
                            <span className="flex items-center gap-2">
                                <span
                                    className={cn(
                                        'inline-block size-2.5 rounded-full',
                                        getOccupancyColorClass(option),
                                    )}
                                    aria-hidden="true"
                                />
                                {option}%
                            </span>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
