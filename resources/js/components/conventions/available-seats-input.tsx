import { Send } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Section } from '@/types/convention';

interface AvailableSeatsInputProps {
    section: Section;
    onUpdate: (availableSeats: number) => void;
}

export default function AvailableSeatsInput({
    section,
    onUpdate,
}: AvailableSeatsInputProps) {
    const { t } = useTranslation();
    const [localValue, setLocalValue] = useState<string | null>(null);

    const displayValue = localValue ?? String(section.available_seats);

    function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
        setLocalValue(e.target.value);
    }

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        const seats = Number(displayValue);
        if (isNaN(seats) || seats < 0) return;

        setLocalValue(null);
        onUpdate(seats);
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-2">
            <Label htmlFor="available-seats">
                {t('section.available_seats.label')}
            </Label>
            <div className="flex items-center gap-2">
                <Input
                    id="available-seats"
                    type="number"
                    min={0}
                    max={section.number_of_seats}
                    value={displayValue}
                    onChange={handleChange}
                    className="flex-1"
                />
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            type="submit"
                            size="default"
                            className="cursor-pointer gap-1.5"
                        >
                            <Send className="size-4" />
                            {t('section.available_seats.send')}
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {t('section.available_seats.tooltip')}
                    </TooltipContent>
                </Tooltip>
            </div>
        </form>
    );
}
