import { OctagonAlert } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { Section } from '@/types/convention';

interface FullButtonProps {
    section: Section;
    onUpdate: () => void;
}

export default function FullButton({ section, onUpdate }: FullButtonProps) {
    const isAlreadyFull = section.occupancy === 100;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="destructive"
                    size="lg"
                    className="w-full cursor-pointer rounded-xl py-6 text-lg font-bold uppercase tracking-wider"
                    disabled={isAlreadyFull}
                    onClick={onUpdate}
                >
                    <OctagonAlert className="size-6" />
                    Full
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {isAlreadyFull ? 'Section is already at 100% capacity' : 'Instantly set this section to 100% occupancy'}
            </TooltipContent>
        </Tooltip>
    );
}
